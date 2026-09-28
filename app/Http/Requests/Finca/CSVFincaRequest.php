<?php

namespace App\Http\Requests\Finca;

use Illuminate\Foundation\Http\FormRequest;

class CSVFincaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la importación masiva de fincas.
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
            'propietario_id' => [
                'nullable',
                'integer',
                'exists:propietarios,id',
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
            'archivo.required'        => 'El archivo de importación es obligatorio.',
            'archivo.file'            => 'El elemento proporcionado debe ser un archivo válido.',
            'archivo.mimes'           => 'El archivo debe tener extensión .csv o .txt.',
            'archivo.max'             => 'El archivo no debe superar los 10MB.',
            'propietario_id.integer'  => 'El ID del propietario debe ser un número entero.',
            'propietario_id.exists'   => 'El propietario seleccionado no existe en el sistema.',
        ];
    }
}
