<?php
/**
 * Conexión a Base de Datos MySQL / MariaDB mediante PDO
 * Sistema Integrado de Radicación, Ruteo y Calidad (SIRCE Cali)
 */

declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'encuesta_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Error de conexión a la BD: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:2rem;max-width:600px;margin:2rem auto;border:1px solid #dc2626;background:#fef2f2;border-radius:8px;color:#991b1b;">' .
                '<h2 style="margin-top:0">Error de conexión a la Base de Datos</h2>' .
                '<p>No fue posible conectar con el servidor MySQL (XAMPP). Verifique que el servicio de MariaDB/MySQL esté activo.</p>' .
                '<p><small>' . htmlspecialchars($e->getMessage()) . '</small></p></div>');
        }
    }

    return $pdo;
}
