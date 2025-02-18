<!-- Giriş Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Giriş Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="alert alert-danger d-none" id="loginError"></div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p class="mb-0">Hesabınız yok mu? <a href="#" onclick="showRegisterModal()">Kayıt olun</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kayıt Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kayıt Ol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm" method="post">
                    <div class="mb-3">
                        <label for="reg_username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="reg_username" name="username" 
                               required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_-]+">
                        <small class="form-text text-muted">
                            3-30 karakter, sadece harf, rakam, tire ve alt çizgi kullanabilirsiniz.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="reg_email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="reg_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="reg_password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="reg_password" name="password" 
                               required minlength="6">
                        <small class="form-text text-muted">En az 6 karakter olmalıdır.</small>
                    </div>
                    <div class="mb-3">
                        <label for="reg_password_confirm" class="form-label">Şifre (Tekrar)</label>
                        <input type="password" class="form-control" id="reg_password_confirm" 
                               name="password_confirm" required minlength="6">
                    </div>
                    <div class="alert alert-danger d-none" id="registerError"></div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Kayıt Ol</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p class="mb-0">Zaten hesabınız var mı? <a href="#" onclick="showLoginModal()">Giriş yapın</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yorum Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yorum Yaz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="commentForm" method="post">
                    <input type="hidden" id="topic_id" name="topic_id">
                    <input type="hidden" id="parent_id" name="parent_id">
                    <div class="mb-3">
                        <label for="comment_content" class="form-label">Yorumunuz</label>
                        <textarea class="form-control" id="comment_content" name="content" 
                                rows="5" required minlength="5"></textarea>
                        <small class="form-text text-muted">En az 5 karakter girmelisiniz.</small>
                    </div>
                    <div class="alert alert-danger d-none" id="commentError"></div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Şikayet Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Şikayet Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm" method="post">
                    <input type="hidden" id="report_content_type" name="content_type">
                    <input type="hidden" id="report_content_id" name="content_id">
                    <div class="mb-3">
                        <label for="report_reason" class="form-label">Şikayet Sebebi</label>
                        <select class="form-select" id="report_reason" name="reason" required>
                            <option value="">Seçiniz...</option>
                            <option value="hakaret">Hakaret/Küfür</option>
                            <option value="spam">Spam/Reklam</option>
                            <option value="yaniltici">Yanıltıcı Bilgi</option>
                            <option value="nefret">Nefret Söylemi</option>
                            <option value="taciz">Taciz/Zorbalık</option>
                            <option value="telif">Telif Hakkı İhlali</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="report_description" class="form-label">Açıklama (İsteğe bağlı)</label>
                        <textarea class="form-control" id="report_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="reportError"></div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 