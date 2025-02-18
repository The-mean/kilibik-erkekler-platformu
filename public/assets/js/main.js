// Modal yönetimi
let loginModal, registerModal, commentModal, reportModal;

document.addEventListener('DOMContentLoaded', function () {
    // Modal örneklerini başlat
    loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
    commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
    reportModal = new bootstrap.Modal(document.getElementById('reportModal'));

    // Form submit olaylarını dinle
    setupFormListeners();

    // Tema değiştiriciyi başlat
    setupThemeToggle();

    // Timeago'yu başlat
    setupTimeago();
});

// Form dinleyicilerini ayarla
function setupFormListeners() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Hata mesajını temizle
            const errorDiv = document.getElementById('loginError');
            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';

            // Form verilerini al
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            // Validasyon
            if (!username || !password) {
                showError('loginError', 'Kullanıcı adı ve şifre gereklidir.');
                return;
            }

            try {
                const response = await fetch('/api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    // Başarılı giriş
                    showToast('Giriş başarılı, yönlendiriliyorsunuz...');
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1000);
                } else {
                    // Hata mesajını göster
                    showError('loginError', data.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('loginError', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
            }
        });
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            await handleRegister(new FormData(this));
        });
    }

    // Comment form
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            await handleComment(new FormData(this));
        });
    }

    // Report form
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            await handleReport(new FormData(this));
        });
    }

    // Topic form
    const topicForm = document.getElementById('topicForm');
    if (topicForm) {
        topicForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const errorDiv = document.getElementById('topicError');
            errorDiv.classList.add('d-none');

            try {
                const formData = {
                    title: document.getElementById('title').value,
                    content: document.getElementById('content').value,
                    category_id: document.getElementById('category').value,
                    tags: Array.from(document.getElementById('tags').selectedOptions).map(option => option.value)
                };

                const response = await fetch('/api/submit_topic.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = `/topic.php?id=${data.topic_id}`;
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Hata:', error);
                errorDiv.textContent = 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
                errorDiv.classList.remove('d-none');
            }
        });
    }
}

// Modal gösterme fonksiyonları
function showLoginModal() {
    if (loginModal) loginModal.show();
}

function showRegisterModal() {
    if (registerModal) registerModal.show();
}

function showCommentModal(topicId, parentId = null) {
    document.getElementById('topic_id').value = topicId;
    document.getElementById('parent_id').value = parentId || '';
    commentModal?.show();
}

function showReportModal(contentType, contentId) {
    document.getElementById('report_content_type').value = contentType;
    document.getElementById('report_content_id').value = contentId;
    reportModal?.show();
}

// API istekleri
async function handleLogin(formData) {
    try {
        const response = await fetch('/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError('loginError', data.message);
        }
    } catch (error) {
        showError('loginError', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}

async function handleRegister(formData) {
    try {
        const response = await fetch('/api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError('registerError', data.message);
        }
    } catch (error) {
        showError('registerError', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}

async function handleComment(formData) {
    try {
        const response = await fetch('/api/submit_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            commentModal?.hide();
            // Yorumu DOM'a ekle
            const commentsContainer = document.querySelector('.comments-container');
            if (commentsContainer && data.comment.html) {
                commentsContainer.insertAdjacentHTML('beforeend', data.comment.html);
            }
            showToast(data.message, 'success');
        } else {
            showError('commentError', data.message);
        }
    } catch (error) {
        showError('commentError', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}

async function handleReport(formData) {
    try {
        const response = await fetch('/api/report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            reportModal?.hide();
            showToast(data.message, 'success');
        } else {
            showError('reportError', data.message);
        }
    } catch (error) {
        showError('reportError', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}

// Beğeni işlemleri
async function likeComment(commentId) {
    try {
        const response = await fetch('/api/like_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                comment_id: commentId,
                type: 'like'
            })
        });

        const data = await response.json();

        if (data.success) {
            updateLikeUI(commentId, data.stats);
            if (data.userReaction === null) {
                showToast('Beğeni kaldırıldı');
            } else {
                showToast('Yorum beğenildi');
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

// Tema değiştirme
function setupThemeToggle() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // İkon değiştir
            const icon = this.querySelector('i');
            icon.className = newTheme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        });
    }
}

// Timeago kurulumu
function setupTimeago() {
    timeago.register('tr', timeagoTR);

    document.querySelectorAll('time.timeago').forEach(function (element) {
        element.textContent = timeago.format(element.getAttribute('datetime'), 'tr');
    });
}

// Hata mesajını göster
function showError(elementId, message) {
    const errorDiv = document.getElementById(elementId);
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('d-none');
    }
}

// Toast mesajı göster
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-body">
            ${message}
        </div>
    `;
    document.body.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function updateLikeUI(commentId, stats) {
    const likeButton = document.querySelector(`#comment-${commentId} .like-button`);
    const likeCount = document.querySelector(`#comment-${commentId} .like-count`);

    if (likeButton && likeCount) {
        likeCount.textContent = stats.likes;
        likeButton.classList.toggle('active', stats.userReaction === 'like');
    }
}

// Beğenme işlemi
async function likeTopic(topicId) {
    try {
        const response = await fetch('/api/topics/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ topic_id: topicId })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Yer imi işlemi
async function bookmarkTopic(topicId) {
    try {
        const response = await fetch('/api/topics/bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ topic_id: topicId })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Paylaşma işlemi
function shareTopic(topicId) {
    const url = window.location.href;

    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: url
        }).catch(console.error);
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('Bağlantı panoya kopyalandı!');
        }).catch(console.error);
    }
}

// Markdown editörü
class MarkdownEditor {
    constructor(textarea, options = {}) {
        this.textarea = textarea;
        this.options = {
            minLength: options.minLength || 0,
            placeholder: options.placeholder || '',
            autosave: options.autosave || false
        };

        this.init();
    }

    init() {
        // Placeholder ayarla
        this.textarea.placeholder = this.options.placeholder;

        // Otomatik kaydetme
        if (this.options.autosave) {
            this.loadDraft();
            this.textarea.addEventListener('input', () => this.saveDraft());
        }

        // Tab tuşu desteği
        this.textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.textarea.selectionStart;
                const end = this.textarea.selectionEnd;

                this.textarea.value = this.textarea.value.substring(0, start) +
                    '    ' +
                    this.textarea.value.substring(end);

                this.textarea.selectionStart = this.textarea.selectionEnd = start + 4;
            }
        });
    }

    saveDraft() {
        if (this.options.autosave) {
            localStorage.setItem('topic_draft', this.textarea.value);
        }
    }

    loadDraft() {
        if (this.options.autosave) {
            const draft = localStorage.getItem('topic_draft');
            if (draft) {
                this.textarea.value = draft;
            }
        }
    }

    clearDraft() {
        localStorage.removeItem('topic_draft');
    }
} 