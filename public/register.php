<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

$auth = new Auth();
$rateLimiter = new RateLimiter();

// Zaten giriş yapmış kullanıcıları ana sayfaya yönlendir
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Tüm alanları doldurun.';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if ($rateLimiter->checkLimit($ip, 'register')) {
            try {
                if ($auth->register($username, $email, $password)) {
                    // Otomatik giriş yap
                    if ($auth->login($username, $password)) {
                        header('Location: /');
                        exit;
                    }
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Çok fazla kayıt denemesi yaptınız. Lütfen bir süre bekleyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">Kılıbık Erkekler Platformu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">Giriş</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/register.php">Kayıt Ol</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">Kayıt Ol</h1>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="form-group mb-3">
                                <label for="username">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                    required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_-]+"
                                    title="Sadece harf, rakam, tire ve alt çizgi kullanabilirsiniz">
                                <small class="form-text text-muted">
                                    3-30 karakter, sadece harf, rakam, tire ve alt çizgi kullanabilirsiniz.
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="email">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="password">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                    required minlength="6">
                                <small class="form-text text-muted">
                                    En az 6 karakter olmalıdır.
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="password_confirm">Şifre (Tekrar)</label>
                                <input type="password" class="form-control" id="password_confirm" 
                                    name="password_confirm" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Kayıt Ol</button>
                        </form>
                        
                        <div class="mt-3">
                            <p class="mb-0">Zaten hesabınız var mı? <a href="/login.php">Giriş yapın</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 