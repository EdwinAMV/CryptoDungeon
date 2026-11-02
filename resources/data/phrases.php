<?php

// Each template has grammatically compatible slots. Do not mix unrelated word
// lists: the resulting messages should remain clear classroom instructions.
return [
    [
        'template' => 'LA LLAVE DE :material ABRE LA PUERTA DEL :lugar',
        'slots' => [
            ':material' => ['ORO', 'PLATA', 'BRONCE'],
            ':lugar' => ['ARCHIVO', 'REFUGIO', 'TESORO', 'JARDIN', 'LABORATORIO'],
        ],
    ],
    [
        'template' => 'BUSCA EL PERGAMINO JUNTO AL :objeto',
        'slots' => [':objeto' => ['CANDELABRO', 'ESPEJO', 'FAROL', 'ALTAR', 'BAUL']],
    ],
    [
        'template' => 'EL :guardian PROTEGE LA ENTRADA DEL :lugar',
        'slots' => [
            ':guardian' => ['DRAGON', 'GOLEM', 'CABALLERO'],
            ':lugar' => ['ARCHIVO', 'REFUGIO', 'TESORO', 'JARDIN', 'LABORATORIO'],
        ],
    ],
    [
        'template' => 'GIRA LA RUEDA HACIA EL :direccion',
        'slots' => [':direccion' => ['NORTE', 'SUR', 'ESTE', 'OESTE']],
    ],
    [
        'template' => 'LA RUNA DEL :simbolo REVELA EL CAMINO AL :lugar',
        'slots' => [
            ':simbolo' => ['SOL', 'FUEGO', 'CIELO', 'VIENTO'],
            ':lugar' => ['ARCHIVO', 'REFUGIO', 'TESORO', 'JARDIN'],
        ],
    ],
    [
        'template' => 'COLOCA EL CRISTAL :color SOBRE EL ALTAR',
        'slots' => [':color' => ['ROJO', 'AZUL', 'VERDE', 'BLANCO']],
    ],
];
