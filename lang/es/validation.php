<?php

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'array' => 'El campo :attribute debe ser una lista válida.',
    'between' => [
        'array' => 'El campo :attribute debe contener entre :min y :max elementos.',
        'file' => 'El archivo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe contener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'digits' => 'El campo :attribute debe contener exactamente :digits dígitos numéricos.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'file' => 'El campo :attribute debe ser un archivo válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'Cada archivo de evidencia no debe superar los 5 MB.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe superar los :max caracteres.',
    ],
    'mimes' => 'Las evidencias deben ser archivos con formato JPG, PNG o PDF.',
    'mimetypes' => 'Las evidencias deben ser archivos con formato JPG, PNG o PDF.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elemento(s).',
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'nullable' => 'El campo :attribute puede ser nulo.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'uploaded' => 'Cada archivo de evidencia no debe superar los 5 MB.',
    'uuid' => 'El campo :attribute debe ser un identificador UUID válido.',

    'custom' => [
        'customer_dni' => [
            'required' => 'El DNI es obligatorio.',
            'digits' => 'El DNI debe contener exactamente 8 dígitos numéricos.',
        ],
        'evidences' => [
            'required' => 'Debes adjuntar al menos un archivo de evidencia.',
            'min' => 'Debes adjuntar al menos un archivo de evidencia.',
            'max' => 'Puedes adjuntar como máximo 5 archivos de evidencia.',
        ],
        'evidences.*' => [
            'file' => 'Las evidencias deben ser archivos con formato JPG, PNG o PDF.',
            'mimes' => 'Las evidencias deben ser archivos con formato JPG, PNG o PDF.',
            'max' => 'Cada archivo de evidencia no debe superar los 5 MB.',
            'uploaded' => 'Cada archivo de evidencia no debe superar los 5 MB.',
        ],
    ],

    'attributes' => [
        'order_number' => 'número de pedido',
        'customer_dni' => 'DNI',
        'tracking_code' => 'código de seguimiento',
        'customer_notes' => 'comentarios',
        'evidences' => 'evidencias',
        'items' => 'productos',
        'new_status' => 'nuevo estado',
        'comment' => 'comentario',
    ],
];

