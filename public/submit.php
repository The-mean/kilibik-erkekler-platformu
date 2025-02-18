<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/profanity_filter.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
$db = Database::getInstance();
$rateLimiter = new RateLimiter();
$profanityFilter = new ProfanityFilter();

// Başlık oluşturma limitleri
define('TITLE_MIN_LENGTH', 3);
define('COMMENT_MIN_LENGTH', 5);
define('RATE_LIMIT_WINDOW', 30); // 30 saniye
define('RATE_LIMIT_MAX_TOPICS', 5);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isComment = isset($_POST['topic_id']);
    $topicId = (int)($_POST['topic_id'] ?? 0);
    
    // Temel doğrulamalar
    if ($isComment) {
        if (strlen($content) < COMMENT_MIN_LENGTH) {
            $error = 'Yorum en az ' . COMMENT_MIN_LENGTH . ' karakter olmalıdır.';
        }
    } else {
        if (strlen($title) < TITLE_MIN_LENGTH) {
            $error = 'Başlık en az ' . TITLE_MIN_LENGTH . ' karakter olmalıdır.';
        }
    }

    if (empty($error)) {
        $ip = Security::getIpAddress();
        $userId = $auth->getEffectiveUserId();
        $actionType = $isComment ? 'comment' : 'topic';
        
        // IP banlı mı kontrol et
        if ($rateLimiter->isIpBanned($ip, $actionType)) {
            $error = 'Çok fazla ' . ($isComment ? 'yorum' : 'başlık') . ' girişimi nedeniyle geçici olarak engellendiniz.';
        }
        // Rate limiting kontrolü
        else if (!$rateLimiter->checkLimit($ip, $actionType, $userId)) {
            $error = 'Çok fazla ' . ($isComment ? 'yorum yaptınız' : 'başlık açtınız') . '. Lütfen bekleyin.';
        }
        // Spam kontrolü
        else {
            $text = $isComment ? $content : $title;
            $spamScore = $profanityFilter->getSpamScore($text);
            
            if ($spamScore >= 5) {
                $error = 'İçeriğiniz spam olarak algılandı. Lütfen düzenleyin.';
            } else {
                // Küfür kontrolü ve filtreleme
                if ($isComment) {
                    $content = $profanityFilter->filter($content);
                } else {
                    $title = $profanityFilter->filter($title);
                }
                
                try {
                    if ($isComment) {
                        // Yorum ekleme
                        $result = $db->query(
                            "INSERT INTO comments (topic_id, user_id, content) 
                            VALUES (:topic_id, :user_id, :content)",
                            [
                                ':topic_id' => $topicId,
                                ':user_id' => $userId,
                                ':content' => $content
                            ]
                        );
                        
                        if ($result) {
                            header('Location: /topic.php?id=' . $topicId);
                            exit;
                        }
                    } else {
                        // Başlık ekleme
                        $slug = createSlug($title);
                        
                        // Benzersiz slug oluştur
                        $baseSlug = $slug;
                        $counter = 1;
                        while (isSlugExists($db, $slug)) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }
                        
                        $result = $db->query(
                            "INSERT INTO topics (title, slug, user_id) 
                            VALUES (:title, :slug, :user_id)",
                            [
                                ':title' => $title,
                                ':slug' => $slug,
                                ':user_id' => $userId
                            ]
                        );
                        
                        if ($result) {
                            header('Location: /topic.php?slug=' . $slug);
                            exit;
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Bir hata oluştu: ' . $e->getMessage();
                }
            }
        }
    }
}

// Slug oluşturma fonksiyonu
function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(
        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', ' '],
        ['i', 'g', 'u', 's', 'o', 'c', '-'],
        $str
    );
    $str = preg_replace('/[^a-z0-9\-]/', '', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

// Slug kontrolü
function isSlugExists($db, $slug) {
    $result = $db->query(
        "SELECT COUNT(*) as count FROM topics WHERE slug = :slug",
        [':slug' => $slug]
    );
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isComment ? 'Yorum Yap' : 'Yeni Başlık' ?> - Kılıbık Erkekler Platformu</title>
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
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/profile.php"><?= htmlspecialchars($auth->getDisplayName()) ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">Çıkış</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">
                            <?= $isComment ? 'Yorum Yap' : 'Yeni Başlık Aç' ?>
                        </h1>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <?php if ($isComment): ?>
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                <div class="form-group mb-3">
                                    <label for="content">Yorumunuz</label>
                                    <textarea class="form-control" id="content" name="content" rows="3" 
                                        required minlength="<?= COMMENT_MIN_LENGTH ?>"
                                        placeholder="Yorumunuzu yazın..."><?= htmlspecialchars($content ?? '') ?></textarea>
                                    <small class="form-text text-muted">
                                        En az <?= COMMENT_MIN_LENGTH ?> karakter girmelisiniz.
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="form-group mb-3">
                                    <label for="title">Başlık</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                        required minlength="<?= TITLE_MIN_LENGTH ?>"
                                        value="<?= htmlspecialchars($title ?? '') ?>"
                                        placeholder="Başlık yazın...">
                                    <small class="form-text text-muted">
                                        En az <?= TITLE_MIN_LENGTH ?> karakter girmelisiniz.
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <?= $isComment ? 'Yorum Yap' : 'Başlık Aç' ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Kurallar</h5>
                        <ul class="mb-0">
                            <?php if ($isComment): ?>
                                <li>Yorumunuz en az <?= COMMENT_MIN_LENGTH ?> karakter olmalıdır.</li>
                                <li>Hakaret ve küfür içeren yorumlar sansürlenecektir.</li>
                                <li>Spam yapmak yasaktır.</li>
                            <?php else: ?>
                                <li>Başlığınız en az <?= TITLE_MIN_LENGTH ?> karakter olmalıdır.</li>
                                <li>Her <?= RATE_LIMIT_WINDOW ?> saniyede en fazla <?= RATE_LIMIT_MAX_TOPICS ?> başlık açabilirsiniz.</li>
                                <li>Hakaret ve küfür içeren başlıklar sansürlenecektir.</li>
                                <li>Aynı konuda birden fazla başlık açmayın.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 