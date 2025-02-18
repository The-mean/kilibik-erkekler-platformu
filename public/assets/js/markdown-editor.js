class MarkdownEditor {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            autosave: true,
            autosaveInterval: 30000, // 30 saniye
            minLength: options.minLength || 0,
            maxLength: options.maxLength || 0,
            placeholder: options.placeholder || '',
            previewRender: options.previewRender || this.defaultPreviewRender,
            ...options
        };

        this.init();
    }

    init() {
        // Editör container'ı oluştur
        this.container = document.createElement('div');
        this.container.className = 'markdown-editor';
        this.element.parentNode.insertBefore(this.container, this.element);

        // Toolbar oluştur
        this.createToolbar();

        // Textarea'yı taşı ve özelliklerini ayarla
        this.container.appendChild(this.element);
        this.element.classList.add('markdown-editor-textarea');
        if (this.options.placeholder) {
            this.element.placeholder = this.options.placeholder;
        }

        // Preview alanı oluştur
        this.createPreview();

        // Footer oluştur
        this.createFooter();

        // Event listener'ları ekle
        this.addEventListeners();

        // Otomatik kaydetmeyi başlat
        if (this.options.autosave) {
            this.initAutosave();
        }

        // Kaydedilmiş taslağı yükle
        this.loadDraft();
    }

    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'markdown-editor-toolbar';

        const buttons = [
            { icon: 'bold', title: 'Kalın', action: this.insertBold.bind(this) },
            { icon: 'italic', title: 'İtalik', action: this.insertItalic.bind(this) },
            { icon: 'code', title: 'Kod', action: this.insertCode.bind(this) },
            { icon: 'link', title: 'Link', action: this.insertLink.bind(this) },
            { icon: 'image', title: 'Resim', action: this.insertImage.bind(this) },
            { icon: 'list-ul', title: 'Liste', action: this.insertList.bind(this) },
            { icon: 'quote-left', title: 'Alıntı', action: this.insertQuote.bind(this) },
            { icon: 'eye', title: 'Önizleme', action: this.togglePreview.bind(this) }
        ];

        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.type = 'button';
            button.title = btn.title;
            button.innerHTML = `<i class="fas fa-${btn.icon}"></i>`;
            button.addEventListener('click', btn.action);
            toolbar.appendChild(button);
        });

        this.container.appendChild(toolbar);
    }

    createPreview() {
        this.preview = document.createElement('div');
        this.preview.className = 'markdown-preview';
        this.container.appendChild(this.preview);
    }

    createFooter() {
        const footer = document.createElement('div');
        footer.className = 'markdown-editor-footer';

        // Taslak durumu
        const draftStatus = document.createElement('div');
        draftStatus.className = 'draft-status';
        draftStatus.innerHTML = '<i class="fas fa-save"></i> <span>Kaydedildi</span>';
        this.draftStatus = draftStatus.querySelector('span');

        // Karakter sayacı
        const charCount = document.createElement('div');
        charCount.className = 'char-count';
        this.charCount = charCount;

        footer.appendChild(draftStatus);
        footer.appendChild(charCount);
        this.container.appendChild(footer);
    }

    addEventListeners() {
        // Input değişikliklerini dinle
        this.element.addEventListener('input', () => {
            this.updateCharCount();
            this.updateDraftStatus('Kaydediliyor...');
            this.saveDraft();
        });

        // Ctrl+S ile kaydetme
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 's' && this.element === document.activeElement) {
                e.preventDefault();
                this.saveDraft();
            }
        });
    }

    initAutosave() {
        setInterval(() => {
            if (this.isDirty) {
                this.saveDraft();
            }
        }, this.options.autosaveInterval);
    }

    saveDraft() {
        const content = this.element.value;
        const draftKey = this.getDraftKey();

        localStorage.setItem(draftKey, content);
        this.isDirty = false;

        setTimeout(() => {
            this.updateDraftStatus('Kaydedildi');
        }, 1000);
    }

    loadDraft() {
        const draftKey = this.getDraftKey();
        const draft = localStorage.getItem(draftKey);

        if (draft) {
            this.element.value = draft;
            this.updateCharCount();
        }
    }

    clearDraft() {
        const draftKey = this.getDraftKey();
        localStorage.removeItem(draftKey);
        this.element.value = '';
        this.updateCharCount();
        this.updateDraftStatus('Taslak temizlendi');
    }

    getDraftKey() {
        return `markdown_draft_${this.element.id || this.element.name || 'editor'}`;
    }

    updateDraftStatus(status) {
        this.draftStatus.textContent = status;
    }

    updateCharCount() {
        const length = this.element.value.length;
        let text = `${length} karakter`;

        if (this.options.maxLength) {
            text += ` / ${this.options.maxLength}`;
        }

        this.charCount.textContent = text;
    }

    insertBold() {
        this.wrapText('**', '**');
    }

    insertItalic() {
        this.wrapText('_', '_');
    }

    insertCode() {
        const selection = this.getSelection();
        if (selection.includes('\n')) {
            this.wrapText('```\n', '\n```');
        } else {
            this.wrapText('`', '`');
        }
    }

    insertLink() {
        const selection = this.getSelection();
        const url = prompt('URL girin:');
        if (url) {
            this.replaceSelection(`[${selection || 'Link'}](${url})`);
        }
    }

    insertImage() {
        const selection = this.getSelection();
        const url = prompt('Resim URL girin:');
        if (url) {
            this.replaceSelection(`![${selection || 'Resim'}](${url})`);
        }
    }

    insertList() {
        const selection = this.getSelection();
        const lines = selection.split('\n');
        const listItems = lines.map(line => `- ${line}`).join('\n');
        this.replaceSelection(listItems || '- ');
    }

    insertQuote() {
        const selection = this.getSelection();
        const lines = selection.split('\n');
        const quote = lines.map(line => `> ${line}`).join('\n');
        this.replaceSelection(quote || '> ');
    }

    togglePreview() {
        if (this.preview.classList.contains('active')) {
            this.preview.classList.remove('active');
            this.element.style.display = '';
        } else {
            this.preview.classList.add('active');
            this.element.style.display = 'none';
            this.renderPreview();
        }
    }

    renderPreview() {
        const content = this.element.value;
        this.preview.innerHTML = this.options.previewRender(content);
    }

    defaultPreviewRender(content) {
        return marked.parse(content);
    }

    getSelection() {
        const start = this.element.selectionStart;
        const end = this.element.selectionEnd;
        return this.element.value.substring(start, end);
    }

    wrapText(before, after) {
        const selection = this.getSelection();
        this.replaceSelection(before + selection + after);
    }

    replaceSelection(text) {
        const start = this.element.selectionStart;
        const end = this.element.selectionEnd;

        this.element.value = this.element.value.substring(0, start) +
            text +
            this.element.value.substring(end);

        this.element.focus();
        this.element.selectionStart = start + text.length;
        this.element.selectionEnd = start + text.length;

        this.updateCharCount();
        this.isDirty = true;
    }
} 