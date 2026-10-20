<?php
/**
 * Konfigurasi koneksi database ZENIN (XAMPP default).
 * Ubah DB_PASS bila MySQL Anda memakai password.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'ZENIN';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $ex) {
        http_response_code(500);
        exit('<h2 style="font-family:sans-serif">Koneksi database gagal</h2>'
            . '<p style="font-family:sans-serif">Pastikan MySQL di XAMPP sudah Start dan file '
            . '<code>database/zenin.sql</code> sudah diimpor ke phpMyAdmin.</p>');
    }
    return $pdo;
}
