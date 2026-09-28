<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EtapaClassifierService
{
    /**
     * Sincroniza la etapa actual de un animal en la base de datos.
     * Calcula la etapa adecuada según la edad, sexo, peso y tipo de animal,
     * y si hay cambios, cierra la etapa anterior y abre la nueva en el historial.
     *
     * @param Animal $animal El modelo del animal a evaluar.
     * @param float|null $latestWeight El último peso registrado (opcional).
     * @return array Resumen del proceso de sincronización.
     */
    public function syncCurrentEtapa(Animal $animal, ?float $latestWeight = null): array
    {
        // Carga la relación de composición de raza si aún no está cargada.
        $animal->loadMissing('composicionRaza');

        // Calcula la edad en días a partir de la fecha de nacimiento.
        $ageDays = Carbon::parse($animal->fecha_nacimiento)->startOfDay()->diffInDays(now()->startOfDay());
        $normalizedSex = $this->normalizeSex($animal->sexo);
        $tipoAnimalId = $animal->composicionRaza?->tipo_animal_id;

        // Resuelve la etapa destino basada en las reglas de negocio.
        $targetEtapa = $this->resolveTargetEtapa($tipoAnimalId, $normalizedSex, $ageDays, $latestWeight);

        if (!$targetEtapa) {
            return [
                'changed' => false,
                'age_days' => $ageDays,
                'target_etapa_id' => null,
                'target_etapa' => null,
                'reason' => 'No se pudo determinar la etapa para los datos actuales',
                'weight_check' => $this->buildWeightCheck($ageDays, $latestWeight),
            ];
        }

        $today = now()->toDateString();

        // Transacción de base de datos para asegurar consistencia al actualizar el historial de etapas.
        $changed = DB::transaction(function () use ($animal, $targetEtapa, $today) {
            // Busca la etapa activa actual del animal (sin fecha de fin o con fin en el futuro).
            $active = EtapaAnimal::where('animal_id', $animal->id)
                ->where(function ($q) use ($today) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>', $today);
                })
                ->orderByDesc('fecha_ini')
                ->first();

            // Si ya tiene la etapa destino activa, no hacemos cambios.
            if ($active && (int) $active->etapa_id === (int) $targetEtapa->id) {
                return false;
            }

            // Cerramos la etapa activa actual colocando la fecha de hoy como fecha de fin.
            EtapaAnimal::where('animal_id', $animal->id)
                ->where(function ($q) use ($today) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>', $today);
                })
                ->update(['fecha_fin' => $today]);

            // Verificamos si ya existe un registro previo para esta combinación de animal y etapa destino.
            $existingTarget = EtapaAnimal::where('animal_id', $animal->id)
                ->where('etapa_id', $targetEtapa->id)
                ->first();

            if ($existingTarget) {
                // Si ya existe, reactivamos ese registro limpiando su fecha de fin.
                $existingTarget->update([
                    'fecha_ini' => $today,
                    'fecha_fin' => null,
                ]);
            } else {
                // Si es la primera vez en esta etapa, creamos un nuevo registro.
                EtapaAnimal::create([
                    'animal_id' => $animal->id,
                    'etapa_id' => $targetEtapa->id,
                    'fecha_ini' => $today,
                    'fecha_fin' => null,
                ]);
            }

            return true;
        });

        return [
            'changed' => $changed,
            'age_days' => $ageDays,
            'target_etapa_id' => $targetEtapa->id,
            'target_etapa' => $targetEtapa->nombre,
            'reason' => null,
            'weight_check' => $this->buildWeightCheck($ageDays, $latestWeight),
        ];
    }

    /**
     * Determina la etapa correspondiente para el animal basado en sus atributos y reglas.
     *
     * @param int|null $tipoAnimalId ID del tipo de animal (Vacuno = 1, Bufala = 2).
     * @param string $sex Sexo normalizado ('M' o 'F').
     * @param int $ageDays Edad del animal en días.
     * @param float|null $latestWeight Último peso registrado.
     * @return Etapa|null Retorna el modelo Etapa correspondiente o null.
     */
    private function resolveTargetEtapa(?int $tipoAnimalId, string $sex, int $ageDays, ?float $latestWeight): ?Etapa
    {
        // Reglas personalizadas para Vacuno (ID = 1 en TipoAnimalSeeder)
        if ($tipoAnimalId === 1) {
            $vacunoEtapa = $this->resolveVacunoEtapa($sex, $ageDays, $latestWeight);
            if ($vacunoEtapa) {
                return $vacunoEtapa;
            }
        }

        if (!$tipoAnimalId) {
            return null;
        }

        $sexValues = ($sex === 'H') ? ['H'] : ['M'];

        // Consulta general de etapas basadas en la edad para otros tipos de animales (como Búfala)
        $candidates = Etapa::query()
            ->forTipoAnimal($tipoAnimalId)
            ->where(function ($q) use ($sexValues) {
                $q->whereIn('sexo', $sexValues)
                  ->orWhereNull('sexo');
            })
            ->orderBy('edad_ini')
            ->get();

        return $candidates->first(function (Etapa $etapa) use ($ageDays) {
            return $etapa->includesAge($ageDays);
        });
    }

    /**
     * Resuelve de manera específica las etapas para los animales del tipo Vacuno.
     * Sigue criterios tradicionales basados en edad y peso.
     *
     * @param string $sex Sexo ('M' o 'H').
     * @param int $ageDays Edad en días.
     * @param float|null $latestWeight Último peso registrado.
     * @return Etapa|null Etapa encontrada por nombre.
     */
    private function resolveVacunoEtapa(string $sex, int $ageDays, ?float $latestWeight): ?Etapa
    {
        // Becerro / Becerra: menor o igual a 6 meses (180 días)
        if ($ageDays <= 180) {
            $names = $sex === 'M' ? ['becerro', 'ternero'] : ['becerra', 'ternera'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        // Maute / Mauta: entre 6 meses y 1.5 años (hasta 548 días)
        if ($ageDays <= 548) {
            $names = $sex === 'M' ? ['maute'] : ['mauta'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        // Toro / Vaca: peso mayor o igual a 450 kg (adultez) O edad mayor o igual a 913 días (2.5 años)
        if (($latestWeight !== null && $latestWeight >= 450) || $ageDays >= 913) {
            $names = $sex === 'M' ? ['toro'] : ['vaca'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        // Novillo / Novilla: por descarte (de 1.5 años en adelante pero sin peso adulto y menor a 913 días)
        $names = $sex === 'M' ? ['novillo'] : ['novilla'];
        return $this->findVacunoEtapaByNames($names, $sex);
    }

    /**
     * Busca una etapa de vacuno en la base de datos coincidiendo con una lista de posibles nombres.
     *
     * @param array $names Lista de nombres posibles (en minúsculas).
     * @param string $sex Sexo del animal ('M' o 'H').
     * @return Etapa|null Modelo de Etapa encontrado.
     */
    private function findVacunoEtapaByNames(array $names, string $sex): ?Etapa
    {
        $sexValues = ($sex === 'H') ? ['H'] : ['M'];
        $normalizedNames = array_map('strtolower', $names);

        // Obtenemos las etapas configuradas para vacunos (ID = 1) y el sexo correspondiente o cualquiera
        $candidates = Etapa::query()
            ->forTipoAnimal(1)
            ->where(function ($q) use ($sexValues) {
                $q->whereIn('sexo', $sexValues)
                  ->orWhereNull('sexo');
            })
            ->get();

        return $candidates->first(function (Etapa $etapa) use ($normalizedNames) {
            return in_array(strtolower((string) $etapa->nombre), $normalizedNames, true);
        });
    }

    /**
     * Construye un análisis del peso para saber si el animal cumple con los objetivos esperados para su edad.
     *
     * @param int $ageDays Edad en días.
     * @param float|null $latestWeight Último peso registrado.
     * @return array Resumen del control de peso.
     */
    private function buildWeightCheck(int $ageDays, ?float $latestWeight): array
    {
        $targetWeight = null;

        // Establece objetivos de peso de acuerdo al rango de edad del animal
        if ($ageDays <= 548) {
            $targetWeight = 113.0;
        } elseif ($ageDays <= 913) {
            $targetWeight = 225.0;
        } else {
            $targetWeight = 450.0;
        }

        if ($latestWeight === null) {
            return [
                'latest_weight' => null,
                'target_weight' => $targetWeight,
                'meets_target' => null,
                'status' => 'sin_registro',
            ];
        }

        return [
            'latest_weight' => $latestWeight,
            'target_weight' => $targetWeight,
            'meets_target' => $latestWeight >= $targetWeight,
            'status' => $latestWeight >= $targetWeight ? 'ok' : 'bajo_objetivo',
        ];
    }

    /**
     * Normaliza el campo sexo para usar consistentemente 'M' (Macho) o 'H' (Hembra).
     *
     * @param string|null $sex Sexo original.
     * @return string Sexo normalizado ('M' o 'H').
     */
    private function normalizeSex(?string $sex): string
    {
        $value = strtoupper((string) $sex);

        // 'F' (Femenino) se normaliza a 'H' (Hembra)
        if ($value === 'F' || $value === 'FEMENINO' || $value === 'HEMBRA') {
            return 'H';
        }

        return $value === 'M' ? 'M' : 'H';
    }
}
