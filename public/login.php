<?php
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();

// Zaten giriş yapmış kullanıcıları ana sayfaya yönlendir
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">Giriş Yap</h1>
                        
                        <div class="alert alert-danger d-none" id="loginError"></div>

                        <form id="loginForm" method="post">
                            <div class="form-group mb-3">
                                <label for="username">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="password">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Giriş Yap</button>
                        </form>
                        
                        <div class="mt-3">
                            <p class="mb-0">Hesabınız yok mu? <a href="/register.php">Kayıt olun</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html> 