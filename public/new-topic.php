<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/category_manager.php';

$auth = new Auth();
$categoryManager = new CategoryManager();

// Giriş yapmamış kullanıcıları login sayfasına yönlendir
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Rate limiting kontrolü
$rateLimiter = new RateLimiter();
$ip = Security::getIpAddress();

// Kategorileri ve popüler etiketleri al
$categories = $categoryManager->getAllCategories();
$popularTags = $categoryManager->getPopularTags(10);

// Varlıkları minify et
$assets = minifyAssets();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Konu - Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assets['css'] ?>">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/markdown-editor.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">Yeni Konu</h1>
                        
                        <div class="alert alert-danger d-none" id="topicError"></div>

                        <form id="topicForm" method="post">
                            <div class="form-group mb-3">
                                <label for="title">Başlık</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       required minlength="5" maxlength="255">
                                <small class="form-text text-muted">
                                    En az 5, en fazla 255 karakter.
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="category">Kategori</label>
                                <select class="form-control" id="category" name="category_id" required>
                                    <option value="">Kategori seçin</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="tags">Etiketler</label>
                                <select class="form-control" id="tags" name="tags[]" multiple>
                                    <?php foreach ($popularTags as $tag): ?>
                                    <option value="<?= htmlspecialchars($tag['name']) ?>">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    En az 1, en fazla 5 etiket seçin veya yeni etiket ekleyin.
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="content">İçerik</label>
                                <textarea class="form-control" id="content" name="content" 
                                        rows="10" required minlength="20"></textarea>
                                <small class="form-text text-muted">
                                    En az 20 karakter. Markdown formatı desteklenmektedir.
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <a href="/" class="btn btn-secondary me-2">İptal</a>
                                <button type="submit" class="btn btn-primary">Gönder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?= $assets['js'] ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select2'yi başlat
            $('#tags').select2({
                tags: true,
                tokenSeparators: [',', ' '],
                maximumSelectionLength: 5,
                placeholder: 'Etiket seçin veya yazın',
                language: {
                    maximumSelected: function() {
                        return 'En fazla 5 etiket seçebilirsiniz';
                    }
                }
            });
            
            // Markdown editörünü başlat
            const contentEditor = new MarkdownEditor(document.getElementById('content'), {
                minLength: 20,
                placeholder: 'Konunuzu yazın... Markdown formatı desteklenmektedir.',
                autosave: true
            });
            
            // Form gönderildiğinde taslağı temizle
            document.getElementById('topicForm').addEventListener('submit', function() {
                contentEditor.clearDraft();
            });
        });
    </script>
</body>
</html> 