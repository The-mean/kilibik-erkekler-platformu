<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/security.php';

try {
    $db = Database::getInstance();
    
    // Admin hesabı bilgileri
    $admin = [
        'username' => 'admin',
        'email' => 'admin@kilibikerkekler.com',
        'password' => Security::hashPassword('admin123'),
        'is_admin' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Admin hesabını oluştur
    $db->insert('users', $admin);
    
    echo "Admin hesabı başarıyla oluşturuldu.\n";
    echo "Kullanıcı adı: admin\n";
    echo "Şifre: admin123\n";
    
} catch (Exception $e) {
    die("Hata: " . $e->getMessage() . "\n");
} 