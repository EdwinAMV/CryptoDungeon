<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'email' => 'Introduce un correo electrónico válido.',
    'unique' => 'El valor de :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'in' => 'Selecciona una opción válida para :attribute.',
    'regex' => 'El formato de :attribute no es válido.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no puede superar :max.',
        'string' => 'El campo :attribute no puede superar :max caracteres.',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'code' => 'código',
        'teamName' => 'nombre del equipo',
        'displayName' => 'nombre del alumno',
        'mode' => 'modo',
        'difficulty' => 'dificultad',
        'duration_minutes' => 'duración',
    ],
];
