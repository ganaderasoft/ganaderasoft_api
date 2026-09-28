<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;

class CSVRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la importación masiva.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:csv,txt,text',
            ],
            'finca_id' => [
                'required',
                'integer',
                'exists:fincas,id',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'El archivo de importación es obligatorio.',
            'archivo.file'     => 'El elemento proporcionado debe ser un archivo válido.',
            'archivo.mimes'    => 'El archivo debe tener extensión .csv o .txt.',
            'archivo.max'      => 'El archivo no debe superar los 10MB.',
            'finca_id.required' => 'Debe seleccionar una finca de destino válida.',
            'finca_id.integer'  => 'El ID de la finca debe ser un número entero.',
            'finca_id.exists'   => 'La finca seleccionada no existe en el sistema.',
        ];
    }
}
