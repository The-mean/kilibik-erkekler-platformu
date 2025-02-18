<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Veritabanı dosyasının yolunu al
    $dbFile = __DIR__ . '/../database/sozluk.db';
    
    // Veritabanı dizininin varlığını kontrol et
    $dbDir = dirname($dbFile);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    
    // Veritabanı bağlantısını al
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Kullanıcılar tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        avatar TEXT DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        website TEXT DEFAULT NULL,
        location TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_admin INTEGER DEFAULT 0,
        is_banned INTEGER DEFAULT 0,
        last_login DATETIME,
        failed_login_attempts INTEGER DEFAULT 0,
        last_failed_login DATETIME
    )");

    // Başlıklar tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS topics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        user_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        view_count INTEGER DEFAULT 0,
        is_deleted INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Yorumlar tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        topic_id INTEGER NOT NULL,
        user_id INTEGER,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_deleted INTEGER DEFAULT 0,
        parent_id INTEGER DEFAULT NULL,
        FOREIGN KEY (topic_id) REFERENCES topics(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (parent_id) REFERENCES comments(id)
    )");

    // Beğeniler tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        topic_id INTEGER,
        comment_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (topic_id) REFERENCES topics(id),
        FOREIGN KEY (comment_id) REFERENCES comments(id),
        UNIQUE(user_id, topic_id),
        UNIQUE(user_id, comment_id)
    )");

    // Yer imleri tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        topic_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (topic_id) REFERENCES topics(id),
        UNIQUE(user_id, topic_id)
    )");

    // Şikayetler tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reporter_id INTEGER,
        content_type TEXT NOT NULL,
        content_id INTEGER NOT NULL,
        reason TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'pending',
        FOREIGN KEY (reporter_id) REFERENCES users(id)
    )");

    // Rate limiting tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        action_type TEXT NOT NULL,
        request_count INTEGER DEFAULT 1,
        last_request DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Giriş denemeleri tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        username TEXT NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Aktivite logları tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        description TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Oturum tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT UNIQUE NOT NULL,
        user_id INTEGER NOT NULL,
        ip_address TEXT NOT NULL,
        user_agent TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    echo "Veritabanı tabloları başarıyla oluşturuldu.\n";
    
    // Veritabanı dosyasının izinlerini ayarla
    chmod($dbFile, 0666);
    
} catch (Exception $e) {
    die("Veritabanı oluşturma hatası: " . $e->getMessage() . "\n");
} 