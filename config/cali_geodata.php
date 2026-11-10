<?php
/**
 * División Territorial Oficial de Santiago de Cali
 * Comunas (1 a 22), Barrios Oficiales, Corregimientos y Sedes de Atención Estáticas
 * Requerimientos 2, 3 y 5
 */

declare(strict_types=1);

// Coordenadas centrales de Cali para inicializar mapas
define('CALI_LAT_DEFAULT', 3.4372);
define('CALI_LNG_DEFAULT', -76.5225);
define('CALI_ZOOM_DEFAULT', 12);

// Sedes de Atención Estáticas (Requerimiento 5: Comunas 1, 6, 7, 13, 14, 20, 21 y 17 Principal)
const CALI_PUNTOS_ESTATICOS = [
    [
        'id'           => 1,
        'codigo'       => 'PA-C01',
        'comuna'       => 1,
        'nombre'       => 'Punto de Atención Terrón Colorado',
        'barrio'       => 'Terrón Colorado',
        'direccion'    => 'Av. 4 Oeste # 19-35',
        'telefono'     => '(602) 893-4101',
        'lat'          => 3.4542,
        'lng'          => -76.5583,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 2,
        'codigo'       => 'PA-C06',
        'comuna'       => 6,
        'nombre'       => 'Punto de Atención Los Mangos - Petecuy',
        'barrio'       => 'Petecuy II',
        'direccion'    => 'Cra. 1D # 73-20',
        'telefono'     => '(602) 441-2306',
        'lat'          => 3.4862,
        'lng'          => -76.4951,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 3,
        'codigo'       => 'PA-C07',
        'comuna'       => 7,
        'nombre'       => 'Punto de Atención Alfonso López',
        'barrio'       => 'Alfonso López I',
        'direccion'    => 'Cl. 70 # 7T Bis-12',
        'telefono'     => '(602) 438-9907',
        'lat'          => 3.4687,
        'lng'          => -76.4889,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 4,
        'codigo'       => 'PA-C13',
        'comuna'       => 13,
        'nombre'       => 'Punto de Atención El Vergel - Oriente',
        'barrio'       => 'El Vergel',
        'direccion'    => 'Cra. 32A # 48-15',
        'telefono'     => '(602) 328-5513',
        'lat'          => 3.4215,
        'lng'          => -76.4990,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 5,
        'codigo'       => 'PA-C14',
        'comuna'       => 14,
        'nombre'       => 'Punto de Atención Manuela Beltrán',
        'barrio'       => 'Manuela Beltrán',
        'direccion'    => 'Cra. 26K # 108-04',
        'telefono'     => '(602) 420-1414',
        'lat'          => 3.4190,
        'lng'          => -76.4850,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 6,
        'codigo'       => 'PA-C20',
        'comuna'       => 20,
        'nombre'       => 'Punto de Atención Siloé',
        'barrio'       => 'Siloé',
        'direccion'    => 'Cl. 1 Oeste # 50-10',
        'telefono'     => '(602) 552-8020',
        'lat'          => 3.4180,
        'lng'          => -76.5540,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 7,
        'codigo'       => 'PA-C21',
        'comuna'       => 21,
        'nombre'       => 'Punto de Atención Desepaz',
        'barrio'       => 'Desepaz',
        'direccion'    => 'Cra. 25 # 120-00',
        'telefono'     => '(602) 429-7721',
        'lat'          => 3.4350,
        'lng'          => -76.4720,
        'es_principal' => false,
        'zona'         => 'urbana'
    ],
    [
        'id'           => 8,
        'codigo'       => 'PA-C17',
        'comuna'       => 17,
        'nombre'       => 'Oficina Principal y Punto de Atención Caney / Pasoancho',
        'barrio'       => 'El Caney',
        'direccion'    => 'Cl. 42 # 85-30',
        'telefono'     => '(602) 330-1700',
        'lat'          => 3.3850,
        'lng'          => -76.5330,
        'es_principal' => true,
        'zona'         => 'urbana'
    ],
];

// Catálogo Oficial de Comunas y sus Barrios
const CALI_COMUNAS_BARRIOS = [
    1 => [
        'nombre'  => 'Comuna 1 (Ladera Noroccidente)',
        'lat'     => 3.4550,
        'lng'     => -76.5600,
        'barrios' => ['Terrón Colorado', 'Vista Hermosa', 'Patio Bonito', 'Bajo Aguacatal', 'Palermo', 'Sector La Legua']
    ],
    2 => [
        'nombre'  => 'Comuna 2 (Norte Residencial / Comercial)',
        'lat'     => 3.4680,
        'lng'     => -76.5250,
        'barrios' => ['Granada', 'Versalles', 'San Vicente', 'Santa Mónica', 'La Flora', 'Menga', 'Prados del Norte', 'VIPASA']
    ],
    3 => [
        'nombre'  => 'Comuna 3 (Centro Histórico)',
        'lat'     => 3.4500,
        'lng'     => -76.5350,
        'barrios' => ['San Antonio', 'San Cayetano', 'El Peñón', 'La Merced', 'San Pedro', 'Santa Rosa', 'El Hoyo', 'San Nicolás']
    ],
    4 => [
        'nombre'  => 'Comuna 4 (Nororiente Industrial)',
        'lat'     => 3.4650,
        'lng'     => -76.5150,
        'barrios' => ['Santander', 'Las Delicias', 'Jorge Isaacs', 'Popular', 'Porvenir', 'Manzanares', 'Salomia', 'Sultana']
    ],
    5 => [
        'nombre'  => 'Comuna 5 (Norte)',
        'lat'     => 3.4750,
        'lng'     => -76.5050,
        'barrios' => ['Los Andes', 'La Rivera', 'Chiminangos', 'Metropolitano del Norte', 'Barranquilla', 'Paseo de Los Álamos']
    ],
    6 => [
        'nombre'  => 'Comuna 6 (Nororiente)',
        'lat'     => 3.4860,
        'lng'     => -76.4950,
        'barrios' => ['Petecuy I', 'Petecuy II', 'Petecuy III', 'Los Mangos', 'San Luis', 'Fonaviemcali', 'Jorge Eliécer Gaitán']
    ],
    7 => [
        'nombre'  => 'Comuna 7 (Nororiente Alfonso López)',
        'lat'     => 3.4680,
        'lng'     => -76.4880,
        'barrios' => ['Alfonso López I', 'Alfonso López II', 'Alfonso López III', 'Puerto Mallarino', 'Siete de Agosto', 'Las Ceibas']
    ],
    8 => [
        'nombre'  => 'Comuna 8 (Centro Oriente)',
        'lat'     => 3.4480,
        'lng'     => -76.5080,
        'barrios' => ['Las Américas', 'Atanasio Girardot', 'Benjamín Herrera', 'Chapinero', 'El Trébol', 'Industrial', 'La Base']
    ],
    9 => [
        'nombre'  => 'Comuna 9 (Centro Sur)',
        'lat'     => 3.4390,
        'lng'     => -76.5250,
        'barrios' => ['Junín', 'Guayaquil', 'Aranjuez', 'Manuel María Buenaventura', 'Santa Mónica Belalcázar', 'Breña']
    ],
    10 => [
        'nombre'  => 'Comuna 10 (Sur Oriente)',
        'lat'     => 3.4280,
        'lng'     => -76.5180,
        'barrios' => ['El Guabal', 'San Judas Tadeo I', 'San Judas Tadeo II', 'Santa Elena', 'Departamental', 'Pasoancho']
    ],
    11 => [
        'nombre'  => 'Comuna 11 (Sur Oriente)',
        'lat'     => 3.4220,
        'lng'     => -76.5120,
        'barrios' => ['Aguablanca', 'Los Sauces', '20 de Julio', 'Prados de Oriente', 'San Pedro Claver', 'León XIII']
    ],
    12 => [
        'nombre'  => 'Comuna 12 (Oriente)',
        'lat'     => 3.4350,
        'lng'     => -76.5000,
        'barrios' => ['Nueva Floresta', 'Alfonso Barberena', 'Asturias', 'Eduardo Santos', 'El Paraíso', 'Fenalco Kennedy']
    ],
    13 => [
        'nombre'  => 'Comuna 13 (Oriente / Distrito de Aguablanca)',
        'lat'     => 3.4215,
        'lng'     => -76.4990,
        'barrios' => ['El Vergel', 'El Poblado I', 'El Poblado II', 'Los Robles', 'Rodrigo Lara Bonilla', 'Ricardo Balcázar', 'Calipso']
    ],
    14 => [
        'nombre'  => 'Comuna 14 (Oriente / Aguablanca)',
        'lat'     => 3.4190,
        'lng'     => -76.4850,
        'barrios' => ['Manuela Beltrán', 'Las Orquídeas', 'Alfonso Bonilla Aragón', 'Marroquín I', 'Marroquín II', 'Los Naranjos']
    ],
    15 => [
        'nombre'  => 'Comuna 15 (Oriente Sur)',
        'lat'     => 3.4050,
        'lng'     => -76.4950,
        'barrios' => ['Ciudad Córdoba', 'El Retiro', 'Comuneros I', 'Laureano Gómez', 'Vallado', 'Morichal']
    ],
    16 => [
        'nombre'  => 'Comuna 16 (Sur Oriente)',
        'lat'     => 3.4110,
        'lng'     => -76.5150,
        'barrios' => ['Mariano Ramos', 'República de Israel', 'Unión de Vivienda Popular', 'Antonio Nariño']
    ],
    17 => [
        'nombre'  => 'Comuna 17 (Sur Residencial / Sede Principal)',
        'lat'     => 3.3850,
        'lng'     => -76.5330,
        'barrios' => ['El Caney', 'Ciudad Capri', 'Primero de Mayo', 'Valle del Lili', 'Limonar', 'Santa Anita', 'La Selva', 'El Ingenio']
    ],
    18 => [
        'nombre'  => 'Comuna 18 (Sur Ladera)',
        'lat'     => 3.3920,
        'lng'     => -76.5500,
        'barrios' => ['Meléndez', 'Buenos Aires', 'Polvorines', 'Los Chorros', 'Alto Nápoles', 'Prados del Sur', 'Lourdes']
    ],
    19 => [
        'nombre'  => 'Comuna 19 (Suroccidente)',
        'lat'     => 3.4220,
        'lng'     => -76.5420,
        'barrios' => ['San Fernando Viejo', 'San Fernando Nuevo', 'Tequendama', 'Champagnat', 'Eucarístico', 'Miraflores', 'El Lido']
    ],
    20 => [
        'nombre'  => 'Comuna 20 (Siloé / Ladera)',
        'lat'     => 3.4180,
        'lng'     => -76.5540,
        'barrios' => ['Siloé', 'Belén', 'Belisario Caicedo', 'La Sultana', 'Tierra Blanca', 'Lleras Camargo', 'Cortijo']
    ],
    21 => [
        'nombre'  => 'Comuna 21 (Oriente Extremo / Desepaz)',
        'lat'     => 3.4350,
        'lng'     => -76.4720,
        'barrios' => ['Desepaz', 'Talanga', 'Compartir', 'Pizamos I', 'Pizamos II', 'Pizamos III', 'Potrero Grande', 'Valle Grande']
    ],
    22 => [
        'nombre'  => 'Comuna 22 (Sur Campestre / Universidades)',
        'lat'     => 3.3550,
        'lng'     => -76.5350,
        'barrios' => ['Ciudad Jardín', 'Campestre', 'Parcelaciones Pance', 'La Umbría', 'San Joaquín']
    ]
];

// Los 15 Corregimientos Oficiales de Cali (Zona Rural)
const CALI_CORREGIMIENTOS = [
    'Pance' => [
        'lat' => 3.3280, 'lng' => -76.5620,
        'veredas' => ['Pance Cabecera', 'San Francisco', 'El Peón', 'La Vorágine', 'El Topacio']
    ],
    'Villacarmelo' => [
        'lat' => 3.3650, 'lng' => -76.5820,
        'veredas' => ['La Suiza', 'El Carmen', 'La Vega', 'El Minuto']
    ],
    'La Buitrera' => [
        'lat' => 3.3720, 'lng' => -76.5650,
        'veredas' => ['El Rosario', 'La Luisa', 'Callejón de las Chuchas', 'El Otoño']
    ],
    'Los Andes' => [
        'lat' => 3.4350, 'lng' => -76.5850,
        'veredas' => ['Los Andes Cabecera', 'Quebrada Honda', 'La Selva', 'El Faro']
    ],
    'Pichindé' => [
        'lat' => 3.4380, 'lng' => -76.6200,
        'veredas' => ['Pichindé Centro', 'Peñas Blancas', 'La Playita']
    ],
    'Felidia' => [
        'lat' => 3.4850, 'lng' => -76.6150,
        'veredas' => ['Felidia Centro', 'La Soledad', 'Las Nieves']
    ],
    'El Saladito' => [
        'lat' => 3.4920, 'lng' => -76.5880,
        'veredas' => ['El Saladito Cabecera', 'San Antonio', 'Montañitas']
    ],
    'La Leonera' => [
        'lat' => 3.4730, 'lng' => -76.6340,
        'veredas' => ['La Leonera Centro', 'El Porvenir', 'Pajonales']
    ],
    'Montebello' => [
        'lat' => 3.4880, 'lng' => -76.5450,
        'veredas' => ['Montebello Centro', 'Campo Alegre', 'El Mamey']
    ],
    'Golondrinas' => [
        'lat' => 3.5050, 'lng' => -76.5250,
        'veredas' => ['Golondrinas Centro', 'La Paz', 'La Castilla Alta']
    ],
    'La Castilla' => [
        'lat' => 3.5150, 'lng' => -76.5650,
        'veredas' => ['La Castilla Centro', 'El Limonar', 'Montañitas']
    ],
    'La Elvira' => [
        'lat' => 3.5420, 'lng' => -76.5800,
        'veredas' => ['La Elvira Centro', 'El Aguacatal Rural', 'San Miguel']
    ],
    'La Paz' => [
        'lat' => 3.5220, 'lng' => -76.5400,
        'veredas' => ['La Paz Centro', 'Lomitas']
    ],
    'Navarro' => [
        'lat' => 3.3980, 'lng' => -76.4680,
        'veredas' => ['Navarro Cabecera', 'Paso del Comercio Rural', 'El Estero']
    ],
    'El Hormiguero' => [
        'lat' => 3.3350, 'lng' => -76.4820,
        'veredas' => ['El Hormiguero Cabecera', 'Cascajal', 'Morgan']
    ],
];
