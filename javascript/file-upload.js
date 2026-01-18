/**
 * File Upload Handler for Chat Application
 * Supports drag-and-drop, paste, and click-to-upload
 */

class FileUploadHandler {
    constructor(options = {}) {
        this.conversationWith = options.conversationWith || null;
        this.onUploadStart = options.onUploadStart || (() => {});
        this.onUploadProgress = options.onUploadProgress || (() => {});
        this.onUploadComplete = options.onUploadComplete || (() => {});
        this.onUploadError = options.onUploadError || (() => {});
        
        this.maxSizes = {
            image: 10 * 1024 * 1024,    // 10 MB
            video: 50 * 1024 * 1024,    // 50 MB
            audio: 20 * 1024 * 1024,    // 20 MB
            document: 20 * 1024 * 1024  // 20 MB
        };
        
        this.allowedTypes = {
            image: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
            video: ['video/mp4', 'video/webm', 'video/ogg'],
            audio: ['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav'],
            document: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                      'text/plain', 'application/zip']
        };
        
        this.init();
    }
    
    init() {
        this.setupFileInput();
        this.setupDragAndDrop();
        this.setupPasteHandler();
    }
    
    setupFileInput() {
        // Create hidden file input
        this.fileInput = document.createElement('input');
        this.fileInput.type = 'file';
        this.fileInput.style.display = 'none';
        this.fileInput.multiple = true;
        this.fileInput.accept = 'image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip';
        document.body.appendChild(this.fileInput);
        
        // Handle file selection
        this.fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.handleFiles(files);
            // Reset input
            this.fileInput.value = '';
        });
    }
    
    setupDragAndDrop() {
        const chatBox = document.querySelector('.chat-box');
        const typingArea = document.querySelector('.typing-area');
        
        if (!chatBox || !typingArea) return;
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            chatBox.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        // Highlight drop zone
        ['dragenter', 'dragover'].forEach(eventName => {
            chatBox.addEventListener(eventName, () => {
                chatBox.classList.add('drag-over');
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            chatBox.addEventListener(eventName, () => {
                chatBox.classList.remove('drag-over');
            });
        });
        
        // Handle dropped files
        chatBox.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            this.handleFiles(files);
        });
    }
    
    setupPasteHandler() {
        document.addEventListener('paste', (e) => {
            const items = e.clipboardData.items;
            const files = [];
            
            for (let item of items) {
                if (item.kind === 'file') {
                    files.push(item.getAsFile());
                }
            }
            
            if (files.length > 0) {
                e.preventDefault();
                this.handleFiles(files);
            }
        });
    }
    
    openFileDialog() {
        this.fileInput.click();
    }
    
    handleFiles(files) {
        files.forEach(file => {
            // Validate file
            const validation = this.validateFile(file);
            if (!validation.valid) {
                this.onUploadError(validation.error);
                return;
            }
            
            // Show preview if image
            if (file.type.startsWith('image/')) {
                this.showImagePreview(file);
            } else {
                // Upload directly for non-images
                this.uploadFile(file);
            }
        });
    }
    
    validateFile(file) {
        // Determine file category
        let category = null;
        let fileType = file.type;
        
        for (let cat in this.allowedTypes) {
            if (this.allowedTypes[cat].includes(fileType)) {
                category = cat;
                break;
            }
        }
        
        if (!category) {
            return { valid: false, error: `File type not supported: ${file.name}` };
        }
        
        // Check file size
        if (file.size > this.maxSizes[category]) {
            const maxSizeMB = this.maxSizes[category] / (1024 * 1024);
            return { valid: false, error: `File too large. Maximum size for ${category}: ${maxSizeMB}MB` };
        }
        
        return { valid: true, category: category };
    }
    
    showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            // Create preview modal
            const modal = this.createPreviewModal(e.target.result, file);
            document.body.appendChild(modal);
        };
        reader.readAsDataURL(file);
    }
    
    createPreviewModal(imageSrc, file) {
        const modal = document.createElement('div');
        modal.className = 'file-preview-modal';
        modal.innerHTML = `
            <div class="preview-content">
                <div class="preview-header">
                    <h3>Send Image</h3>
                    <button class="close-preview">&times;</button>
                </div>
                <div class="preview-body">
                    <img src="${imageSrc}" alt="Preview">
                    <p class="file-name">${file.name}</p>
                    <p class="file-size">${this.formatFileSize(file.size)}</p>
                </div>
                <div class="preview-footer">
                    <button class="btn-cancel">Cancel</button>
                    <button class="btn-send">Send</button>
                </div>
            </div>
        `;
        
        // Handle actions
        modal.querySelector('.close-preview').addEventListener('click', () => {
            modal.remove();
        });
        
        modal.querySelector('.btn-cancel').addEventListener('click', () => {
            modal.remove();
        });
        
        modal.querySelector('.btn-send').addEventListener('click', () => {
            modal.remove();
            this.uploadFile(file);
        });
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        return modal;
    }
    
    uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('conversation_with', this.conversationWith);
        
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                this.onUploadProgress(percent, file.name);
            }
        });
        
        // Handle upload completion
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                this.onUploadComplete(response, file);
            } else {
                const error = xhr.responseText ? JSON.parse(xhr.responseText) : { error: 'Upload failed' };
                this.onUploadError(error.error || 'Upload failed');
            }
        });
        
        // Handle errors
        xhr.addEventListener('error', () => {
            this.onUploadError('Network error during upload');
        });
        
        // Start upload
        this.onUploadStart(file.name);
        xhr.open('POST', 'api/v1/files/upload.php');
        xhr.send(formData);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    setConversation(userId) {
        this.conversationWith = userId;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FileUploadHandler;
}
