<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ads.php';

// AdManager örneğini oluştur
$adManager = new AdManager($auth);

// ... existing code ...

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Temel meta etiketleri -->
    // ... existing code ...
    
    <!-- Google AdSense -->
    <?= $adManager->getAdScript() ?>
    <?= $adManager->getAdStyle() ?>
    
    <!-- Google Analytics -->
    // ... existing code ...
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        // ... existing code ...
    </nav>
    
    <?= $adManager->getAdCode('header') ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Ana içerik alanı -->
                <?= $adManager->getAdCode('content') ?>
            </div>
            <div class="col-md-4">
                <!-- Sidebar -->
                <?= $adManager->getAdCode('sidebar') ?>
            </div>
        </div>
    </div>
    
    <?= $adManager->getAdCode('mobile') ?>
</body>
</html>
