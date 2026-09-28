<?php

namespace App\Services\Finca;

use App\Models\Finca;
use App\Models\Hierro;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Terreno;
use App\Models\User;
use App\Services\BaseService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FincaImportService extends BaseService
{
    /**
     * Detecta automáticamente el delimitador del archivo (coma, punto y coma, tabulador o pipe).
     *
     * @param string $line Primera línea o cabecera del archivo.
     * @return string Delimitador detectado.
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
            'nombre', 'finca', 'nombre_finca', 'finca_nombre' => 'nombre',
            'explotacion_tipo', 'tipo_explotacion', 'explotacion', 'tipo', 'sistema' => 'explotacion_tipo',
            'identificador_hierro', 'hierro', 'marca', 'identificador', 'hierro_id', 'codigo_hierro' => 'identificador_hierro',
            'superficie', 'hectareas', 'superficie_ha', 'area', 'tamano', 'superficie_total' => 'superficie',
            'relieve', 'tipo_relieve', 'topografia' => 'relieve',
            'suelo_textura', 'textura_suelo', 'suelo', 'textura' => 'suelo_textura',
            'ph_suelo', 'ph' => 'ph_suelo',
            'precipitacion', 'lluvia' => 'precipitacion',
            'velocidad_viento', 'viento' => 'velocidad_viento',
            'temp_anual', 'temperatura', 'temp' => 'temp_anual',
            'temp_min' => 'temp_min',
            'temp_max' => 'temp_max',
            'radiacion' => 'radiacion',
            'fuente_agua', 'agua', 'fuente' => 'fuente_agua',
            'caudal_disponible', 'caudal' => 'caudal_disponible',
            'riego_metodo', 'metodo_riego', 'riego' => 'riego_metodo',
            'cedula_propietario', 'propietario_cedula', 'cedula' => 'cedula_propietario',
            'propietario_id', 'id_propietario' => 'propietario_id',
            default => $clean,
        };
    }

    /**
     * Normaliza el tipo de explotación.
     *
     * @param string|null $raw
     * @return string
     */
    public function normalizeExplotacionTipo(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return 'Mixto';
        }

        $clean = strtolower(trim($raw));

        if (str_starts_with($clean, 'int')) {
            return 'Intensiva';
        }
        if (str_starts_with($clean, 'ext')) {
            return 'Extensiva';
        }
        if (str_starts_with($clean, 'mix')) {
            return 'Mixto';
        }
        if (str_starts_with($clean, 'lec')) {
            return 'Lechero';
        }
        if (str_starts_with($clean, 'ceb')) {
            return 'Ceba';
        }

        return ucfirst(trim($raw));
    }

    /**
     * Parsea y estructura las filas del archivo delimitado.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function parseFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Normalizar saltos de línea
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        if (empty($lines)) {
            return [];
        }

        $separator = $this->identificarSeparador($lines[0]);

        $firstRow = str_getcsv($lines[0], $separator);
        $firstRowClean = array_map([$this, 'normalizeHeader'], $firstRow);

        $hasHeader = in_array('nombre', $firstRowClean, true) || in_array('explotacion_tipo', $firstRowClean, true);

        $headers = [];
        $startIndex = 0;

        if ($hasHeader) {
            $headers = $firstRowClean;
            $startIndex = 1;
        } else {
            // Mapeo posicional por defecto
            $headers = [
                0 => 'nombre',
                1 => 'explotacion_tipo',
                2 => 'identificador_hierro',
                3 => 'superficie',
                4 => 'relieve',
                5 => 'fuente_agua',
            ];
        }

        $rows = [];
        for ($i = $startIndex; $i < count($lines); $i++) {
            $rawCells = str_getcsv($lines[$i], $separator);
            if (empty(array_filter($rawCells, fn ($c) => trim($c) !== ''))) {
                continue; // Saltar líneas completamente vacías
            }

            $rowData = ['linea' => $i + 1];
            foreach ($headers as $colIndex => $colName) {
                $val = isset($rawCells[$colIndex]) ? trim($rawCells[$colIndex]) : null;
                $rowData[$colName] = ($val === '' || $val === 'null' || $val === 'NULL') ? null : $val;
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Importa masivamente las fincas validadas dentro de una transacción DB atómica.
     *
     * @param UploadedFile $file
     * @param int|null $propietarioId
     * @param User $user
     * @return array
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function importFincas(UploadedFile $file, ?int $propietarioId, User $user): array
    {
        $rows = $this->parseFile($file);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo está vacío o no contiene filas con datos válidos.',
            ]);
        }

        // Cache local de propietarios por cédula
        $propietariosPorCedula = [];
        $errors = [];
        $validatedData = [];

        // 1. Fase de validación de cada fila
        foreach ($rows as $index => $row) {
            $linea = $row['linea'] ?? ($index + 1);

            // A. Resolver propietario
            $rowPropietarioId = null;
            if (!empty($row['propietario_id']) && is_numeric($row['propietario_id'])) {
                $rowPropietarioId = (int) $row['propietario_id'];
            } elseif (!empty($row['cedula_propietario'])) {
                $cedula = trim($row['cedula_propietario']);
                if (!isset($propietariosPorCedula[$cedula])) {
                    $persona = Persona::where('cedula', $cedula)->first();
                    $prop = $persona ? Propietario::where('persona_id', $persona->id)->first() : null;
                    $propietariosPorCedula[$cedula] = $prop ? $prop->id : null;
                }
                $rowPropietarioId = $propietariosPorCedula[$cedula];
            } elseif ($propietarioId) {
                $rowPropietarioId = (int) $propietarioId;
            } elseif ($user->propietario) {
                $rowPropietarioId = $user->propietario->id;
            } elseif ($user->persona && $user->persona->propietario) {
                $rowPropietarioId = $user->persona->propietario->id;
            }

            if (!$rowPropietarioId) {
                $errors[] = "Línea {$linea}: No se pudo determinar el propietario para la finca. Especifique un propietario válido o su cédula.";
                continue;
            }

            // B. Verificar permisos para este propietario
            if ($user->cannot('create', [Finca::class, $rowPropietarioId])) {
                $errors[] = "Línea {$linea}: No tiene permisos para registrar fincas para el propietario ID {$rowPropietarioId}.";
                continue;
            }

            // C. Validar Nombre
            $nombre = isset($row['nombre']) ? trim($row['nombre']) : '';
            if (empty($nombre)) {
                $errors[] = "Línea {$linea}: El nombre de la finca es obligatorio.";
            } elseif (mb_strlen($nombre) > 25) {
                $errors[] = "Línea {$linea}: El nombre '{$nombre}' excede el límite máximo de 25 caracteres.";
            }

            // D. Validar Tipo de Explotación
            $rawExplotacion = $row['explotacion_tipo'] ?? null;
            if (empty($rawExplotacion)) {
                $errors[] = "Línea {$linea}: El tipo de explotación es obligatorio (ej: Mixto, Intensiva, Extensiva).";
            }
            $explotacionTipo = $this->normalizeExplotacionTipo($rawExplotacion);

            // E. Validar Superficie (si viene)
            $superficie = null;
            if (isset($row['superficie']) && $row['superficie'] !== null && $row['superficie'] !== '') {
                $cleanSup = str_replace(',', '.', trim($row['superficie']));
                if (!is_numeric($cleanSup) || (float)$cleanSup < 0) {
                    $errors[] = "Línea {$linea}: La superficie '{$row['superficie']}' debe ser un valor numérico positivo.";
                } else {
                    $superficie = (float) $cleanSup;
                }
            }

            // F. Validar Identificador de Hierro
            $hierroIdentificador = isset($row['identificador_hierro']) && trim($row['identificador_hierro']) !== ''
                ? trim($row['identificador_hierro'])
                : null;
            if ($hierroIdentificador && mb_strlen($hierroIdentificador) > 20) {
                $errors[] = "Línea {$linea}: El identificador de hierro '{$hierroIdentificador}' excede los 20 caracteres.";
            }

            // Guardar fila validada si no hubo fallos en esta línea
            $validatedData[] = [
                'linea'                 => $linea,
                'propietario_id'        => $rowPropietarioId,
                'nombre'                => $nombre,
                'explotacion_tipo'      => $explotacionTipo,
                'identificador_hierro'  => $hierroIdentificador,
                'terreno'               => [
                    'superficie'        => $superficie,
                    'relieve'           => isset($row['relieve']) ? mb_substr(trim($row['relieve']), 0, 9) : null,
                    'suelo_textura'     => isset($row['suelo_textura']) ? mb_substr(trim($row['suelo_textura']), 0, 25) : null,
                    'ph_suelo'          => isset($row['ph_suelo']) ? mb_substr(trim($row['ph_suelo']), 0, 2) : null,
                    'precipitacion'     => isset($row['precipitacion']) && is_numeric($row['precipitacion']) ? (float)$row['precipitacion'] : null,
                    'velocidad_viento'  => isset($row['velocidad_viento']) && is_numeric($row['velocidad_viento']) ? (float)$row['velocidad_viento'] : null,
                    'temp_anual'        => isset($row['temp_anual']) ? mb_substr(trim($row['temp_anual']), 0, 4) : null,
                    'temp_min'          => isset($row['temp_min']) ? mb_substr(trim($row['temp_min']), 0, 4) : null,
                    'temp_max'          => isset($row['temp_max']) ? mb_substr(trim($row['temp_max']), 0, 4) : null,
                    'radiacion'         => isset($row['radiacion']) && is_numeric($row['radiacion']) ? (float)$row['radiacion'] : null,
                    'fuente_agua'       => isset($row['fuente_agua']) ? mb_substr(trim($row['fuente_agua']), 0, 25) : null,
                    'caudal_disponible' => isset($row['caudal_disponible']) && is_numeric($row['caudal_disponible']) ? (int)$row['caudal_disponible'] : null,
                    'riego_metodo'      => isset($row['riego_metodo']) ? mb_substr(trim($row['riego_metodo']), 0, 18) : null,
                ],
            ];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'import_errors' => $errors,
            ]);
        }

        // 2. Fase de ejecución transaccional
        return DB::transaction(function () use ($validatedData, $user) {
            $fincasCreadas = [];

            foreach ($validatedData as $item) {
                $finca = Finca::create([
                    'propietario_id'   => $item['propietario_id'],
                    'nombre'           => $item['nombre'],
                    'explotacion_tipo' => $item['explotacion_tipo'],
                    'archivado'        => false,
                ]);

                // Registrar Hierro si fue indicado
                if (!empty($item['identificador_hierro'])) {
                    Hierro::create([
                        'finca_id'       => $finca->id,
                        'propietario_id' => $item['propietario_id'],
                        'identificador'  => $item['identificador_hierro'],
                    ]);
                }

                // Registrar Terreno si tiene datos relevantes
                $terrenoData = array_filter($item['terreno'], fn ($v) => $v !== null);
                if (!empty($terrenoData)) {
                    $terrenoData['finca_id'] = $finca->id;
                    Terreno::create($terrenoData);
                }

                // Asignar al usuario si no es admin y no es el dueño directo
                if (!$user->isAdmin()) {
                    $isTheOwner = $user->propietario && $user->propietario->id === (int) $item['propietario_id'];
                    if (!$isTheOwner) {
                        $finca->users()->syncWithoutDetaching([
                            $user->id => [
                                'access_level' => 'operator',
                                'is_default'   => false,
                                'status'       => 'active',
                            ],
                        ]);
                    }
                }

                $fincasCreadas[] = [
                    'id'               => $finca->id,
                    'nombre'           => $finca->nombre,
                    'explotacion_tipo' => $finca->explotacion_tipo,
                    'propietario_id'   => $finca->propietario_id,
                ];
            }

            return [
                'total_creadas' => count($fincasCreadas),
                'fincas'        => $fincasCreadas,
            ];
        });
    }

    /**
     * Genera el contenido de una plantilla CSV estándar de ejemplo.
     *
     * @return string
     */
    public function generateTemplate(): string
    {
        $headers = [
            'nombre',
            'explotacion_tipo',
            'identificador_hierro',
            'superficie',
            'relieve',
            'fuente_agua',
        ];

        $samples = [
            [
                'Hacienda Santa Ines',
                'Mixto',
                'HSI-01',
                '150.5',
                'Plano',
                'Rio',
            ],
            [
                'Finca El Porvenir',
                'Intensiva',
                'FEP-02',
                '85.0',
                'Ondulado',
                'Pozo',
            ],
            [
                'Agropecuaria San Jose',
                'Extensiva',
                '',
                '220.0',
                'Plano',
                'Quebrada',
            ],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($samples as $sample) {
            fputcsv($output, $sample);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
