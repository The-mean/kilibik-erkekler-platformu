<?php
// Hata raporlamayı ayarla
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Gerekli dosyaları dahil et
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/profanity_filter.php';

// Auth sınıfını başlat
$auth = new Auth();

// Veritabanı bağlantısını al
$db = Database::getInstance();

// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

// Karakter setini ayarla
mb_internal_encoding('UTF-8'); 