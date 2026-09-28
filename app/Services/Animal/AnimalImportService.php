<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\ComposicionRaza;
use App\Models\EstadoAnimal;
use App\Models\EstadoSalud;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use App\Models\Finca;
use App\Models\PesoCorporal;
use App\Models\Rebano;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnimalImportService extends BaseService
{
    /**
     * Constructor del servicio.
     * Inyecta el clasificador de etapas para sincronizar automáticamente el crecimiento biológico.
     *
     * @param EtapaClassifierService $etapaClassifier
     */
    public function __construct(
        private EtapaClassifierService $etapaClassifier
    ) {}

    /**
     * Detecta automáticamente el delimitador del archivo (coma o punto y coma).
     *
     * @param string $line Primera línea o cabecera del archivo.
     * @return string Delimitador detectado (',' o ';').
     */
    public function identificarSeparador(string $line): string
    {
        $countSemicolon = substr_count($line, ';');
        $countComma     = substr_count($line, ',');
        $countTab       = substr_count($line, "\t");
        $countPipe      = substr_count($line, '|');

        $delimiters = [
            ';'  => $countSemicolon,
            ','  => $countComma,
            "\t" => $countTab,
            '|'  => $countPipe,
        ];

        arsort($delimiters);
        $best = array_key_first($delimiters);

        return ($delimiters[$best] > 0) ? $best : ',';
    }

    /**
     * Normaliza los encabezados de columnas ignorando mayúsculas, espacios, acentos y BOM.
     *
     * @param string $header
     * @return string
     */
    public function normalizeHeader(string $header): string
    {
        // Remover BOM UTF-8 si está presente
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = trim($header);

        // Convertir a minúsculas y reemplazar acentos
        $unaccented = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $unaccented ?: $header));
        $clean = trim($clean, '_');

        return match ($clean) {
            'codigo_animal', 'codigo', 'identificador', 'id_animal', 'chapa', 'arete', 'numero' => 'codigo_animal',
            'nombre', 'alias', 'nombre_animal' => 'nombre',
            'sexo', 'genero' => 'sexo',
            'fecha_nacimiento', 'nacimiento', 'fecha_nac', 'fecha' => 'fecha_nacimiento',
            'procedencia', 'origen' => 'procedencia',
            'rebano', 'rebano_nombre', 'nombre_rebano', 'nombre_rebano_', 'lote' => 'rebano',
            'raza', 'nombre_raza', 'composicion_raza', 'raza_id' => 'raza',
            'estado_salud', 'estado', 'salud' => 'estado_salud',
            'peso', 'peso_inicial', 'peso_corporal', 'peso_kg' => 'peso',
            default => $clean,
        };
    }

    /**
     * Normaliza el sexo a 'M' o 'H'.
     *
     * @param string|null $raw
     * @return string|null
     */
    public function normalizeSexo(?string $raw): ?string
    {
        if ($raw === null) return null;
        $val = strtoupper(trim($raw));

        if (in_array($val, ['M', 'MACHO', 'MALE', 'TORO', 'BECERRO', 'NOVILLO', 'MAUTE', '1'])) {
            return 'M';
        }

        if (in_array($val, ['H', 'HEMBRA', 'FEMALE', 'F', 'VACA', 'BECERRA', 'NOVILLA', 'MAUTA', '2'])) {
            return 'H';
        }

        return null;
    }

    /**
     * Parsea una fecha en diversos formatos comunes.
     *
     * @param string|null $raw
     * @return Carbon|null
     */
    public function parseFecha(?string $raw): ?Carbon
    {
        if (!$raw || trim($raw) === '') return null;
        $raw = trim($raw);

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'm/d/Y',
            'd.m.Y',
        ];

        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $raw);
                if ($d && $d->format($fmt) === $raw) {
                    return $d->startOfDay();
                }
            } catch (Exception) {
                // Siguiente formato
            }
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Importa masivamente animales desde un archivo CSV o TXT dentro de una transacción DB.
     *
     * @param UploadedFile $file Archivo subido.
     * @param int $fincaId ID de la finca destino.
     * @param User $user Usuario autenticado.
     * @return array Resumen de la importación y colección de animales creados.
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function importFromCsv(UploadedFile $file, int $fincaId, User $user): array
    {
        $finca = Finca::findOrFail($fincaId);

        // 1. Validar permisos del usuario mediante Policy
        if ($user->cannot('create', [Animal::class, $finca])) {
            throw new AuthorizationException('No tiene permisos para crear animales en esta finca.');
        }

        // 2. Leer archivo y detectar delimitador
        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw ValidationException::withMessages([
                'archivo' => ['No se pudo abrir el archivo para su lectura.'],
            ]);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false || trim($firstLine) === '') {
            fclose($handle);
            throw ValidationException::withMessages([
                'archivo' => ['El archivo proporcionado está vacío.'],
            ]);
        }

        $delimiter = $this->identificarSeparador($firstLine);
        rewind($handle);

        // 3. Leer cabeceras
        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeaders || count($rawHeaders) === 0) {
            fclose($handle);
            throw ValidationException::withMessages([
                'archivo' => ['No se pudieron leer los encabezados del archivo.'],
            ]);
        }

        $headers = array_map([$this, 'normalizeHeader'], $rawHeaders);

        // 4. Pre-validar filas y recolectar errores
        $rows = [];
        $fileCodes = [];
        $errors = [];
        $lineIndex = 1; // 1 = cabecera

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineIndex++;

            // Omitir filas vacías
            if (count($data) === 1 && trim((string)$data[0]) === '') {
                continue;
            }

            // Normalizar conteo de columnas
            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), '');
            } elseif (count($data) > count($headers)) {
                $data = array_slice($data, 0, count($headers));
            }

            $row = array_combine($headers, array_map('trim', $data));

            $codigo = $row['codigo_animal'] ?? '';
            $nombre = $row['nombre'] ?? '';
            $rawSexo = $row['sexo'] ?? '';
            $rawFecha = $row['fecha_nacimiento'] ?? '';

            // Validar unicidad de código dentro del archivo
            if ($codigo !== '') {
                if (isset($fileCodes[$codigo])) {
                    $errors[] = "Fila {$lineIndex}: El código '{$codigo}' está duplicado en el archivo (repetido de la fila {$fileCodes[$codigo]}).";
                } else {
                    $fileCodes[$codigo] = $lineIndex;
                }

                // Validar unicidad en base de datos
                if (Animal::where('codigo_animal', $codigo)->exists()) {
                    $errors[] = "Fila {$lineIndex}: El código de animal '{$codigo}' ya existe registrado en el sistema.";
                }
            }

            // Validar sexo
            $sexo = $this->normalizeSexo($rawSexo);
            if (!$sexo) {
                $errors[] = "Fila {$lineIndex}: El sexo '{$rawSexo}' no es válido. Utilice 'M' (Macho) o 'H' (Hembra).";
            }

            // Validar fecha de nacimiento
            $fechaNacimiento = $this->parseFecha($rawFecha);
            if (!$fechaNacimiento) {
                $errors[] = "Fila {$lineIndex}: La fecha de nacimiento '{$rawFecha}' no tiene un formato válido (ej. YYYY-MM-DD o DD/MM/YYYY).";
            } elseif ($fechaNacimiento->isFuture()) {
                $errors[] = "Fila {$lineIndex}: La fecha de nacimiento '{$rawFecha}' no puede ser una fecha futura.";
            }

            $rows[] = [
                'line'             => $lineIndex,
                'codigo'           => $codigo,
                'nombre'           => $nombre,
                'sexo'             => $sexo,
                'fecha_nacimiento' => $fechaNacimiento,
                'procedencia'      => $row['procedencia'] ?? 'Importación Masiva',
                'rebano'           => $row['rebano'] ?? null,
                'raza'             => $row['raza'] ?? null,
                'estado_salud'     => $row['estado_salud'] ?? null,
                'peso'             => isset($row['peso']) && is_numeric($row['peso']) ? (float)$row['peso'] : null,
            ];
        }

        fclose($handle);

        if (count($rows) === 0) {
            throw ValidationException::withMessages([
                'archivo' => ['El archivo no contiene registros de animales para procesar.'],
            ]);
        }

        // Si existen errores de validación en las filas, abortar antes de la transacción
        if (count($errors) > 0) {
            throw ValidationException::withMessages([
                'filas' => $errors,
            ]);
        }

        // 5. Ejecutar carga transaccional
        return DB::transaction(function () use ($rows, $fincaId, $finca) {
            $createdAnimals  = [];
            $createdRebanos  = [];
            $rebanosCache    = [];
            $razasCache      = [];

            // Pre-cargar rebaños existentes de la finca
            $existingRebanos = Rebano::where('finca_id', $fincaId)->get();
            foreach ($existingRebanos as $reb) {
                $rebanosCache[strtolower(trim($reb->nombre))] = $reb;
            }

            // Pre-cargar razas del catálogo
            $allRazas = ComposicionRaza::all();
            foreach ($allRazas as $rz) {
                $razasCache[strtolower(trim($rz->nombre))] = $rz;
                if ($rz->siglas) {
                    $razasCache[strtolower(trim($rz->siglas))] = $rz;
                }
            }

            $defaultRaza = ComposicionRaza::first();
            $defaultEstado = EstadoSalud::where('nombre', 'Sano')->first() ?? EstadoSalud::first();

            foreach ($rows as $item) {
                // 5.1 Resolver o crear rebaño
                $rebanoNombre = $item['rebano'] ? trim($item['rebano']) : 'Rebaño General';
                $rebKey = strtolower($rebanoNombre);

                if (!isset($rebanosCache[$rebKey])) {
                    $newRebano = Rebano::create([
                        'finca_id'  => $fincaId,
                        'nombre'    => $rebanoNombre,
                        'archivado' => false,
                    ]);
                    $rebanosCache[$rebKey] = $newRebano;
                    $createdRebanos[] = $rebanoNombre;
                }

                $rebano = $rebanosCache[$rebKey];

                // 5.2 Resolver raza
                $razaId = $defaultRaza?->id ?? 1;
                if ($item['raza']) {
                    $rzKey = strtolower(trim($item['raza']));
                    if (isset($razasCache[$rzKey])) {
                        $razaId = $razasCache[$rzKey]->id;
                    } elseif (is_numeric($item['raza']) && ComposicionRaza::where('id', (int)$item['raza'])->exists()) {
                        $razaId = (int)$item['raza'];
                    }
                }

                // 5.3 Resolver estado de salud
                $estadoSaludId = $defaultEstado?->id ?? 1;
                if ($item['estado_salud']) {
                    $est = EstadoSalud::whereRaw('LOWER(nombre) = ?', [strtolower(trim($item['estado_salud']))])->first();
                    if ($est) {
                        $estadoSaludId = $est->id;
                    }
                }

                // 5.4 Crear animal
                $animal = Animal::create([
                    'rebano_id'           => $rebano->id,
                    'nombre'              => $item['nombre'] ?: ($item['codigo'] ?: 'Animal #' . uniqid()),
                    'codigo_animal'       => $item['codigo'] ?: null,
                    'sexo'                => $item['sexo'],
                    'fecha_nacimiento'    => $item['fecha_nacimiento']->toDateString(),
                    'procedencia'         => $item['procedencia'] ?: 'Importación Masiva',
                    'composicion_raza_id' => $razaId,
                    'archivado'           => false,
                ]);

                // 5.5 Registrar Estado Inicial
                EstadoAnimal::create([
                    'animal_id'       => $animal->id,
                    'estado_salud_id' => $estadoSaludId,
                    'fecha_ini'       => $item['fecha_nacimiento']->toDateString(),
                    'fecha_fin'       => null,
                ]);

                // 5.6 Sincronizar Etapa Biológica inicial
                $this->etapaClassifier->syncCurrentEtapa($animal, $item['peso']);

                // 5.7 Registrar Peso Inicial si fue provisto
                if ($item['peso'] !== null && $item['peso'] > 0) {
                    $activeEtapaAnimal = EtapaAnimal::where('animal_id', $animal->id)
                        ->where(function ($q) {
                            $q->whereNull('fecha_fin')
                              ->orWhere('fecha_fin', '>=', now()->toDateString());
                        })
                        ->orderByDesc('fecha_ini')
                        ->first();

                    if ($activeEtapaAnimal) {
                        PesoCorporal::create([
                            'animal_etapa_id' => $activeEtapaAnimal->id,
                            'peso'            => $item['peso'],
                            'fecha_peso'      => now()->toDateString(),
                        ]);
                    }
                }

                $createdAnimals[] = $animal->load(['rebano.finca', 'composicionRaza']);
            }

            return [
                'total_procesados' => count($createdAnimals),
                'rebanos_creados'  => array_values(array_unique($createdRebanos)),
                'finca'            => [
                    'id'     => $finca->id,
                    'nombre' => $finca->nombre,
                ],
                'animales'         => $createdAnimals,
            ];
        });
    }
}
