const form = document.querySelector(".typing-area"),
incoming_id = form.querySelector(".incoming_id").value,
inputField = form.querySelector(".input-field"),
sendBtn = form.querySelector("button"),
chatBox = document.querySelector(".chat-box");

// Get current user ID from session
let currentUserId = null;
fetch('php/get-session-user.php')
    .then(r => r.json())
    .then(data => {
        currentUserId = data.unique_id;
        // Initialize WebSocket
        initWebSocket();
        // Initialize file upload
        initFileUpload();
    });

let wsClient = null;
let typingTimeout = null;
let fileUploadHandler = null;

form.onsubmit = (e)=>{
    e.preventDefault();
}

inputField.focus();

// Typing indicator
inputField.onkeyup = ()=>{
    if(inputField.value != ""){
        sendBtn.classList.add("active");
        
        // Send typing indicator
        if (wsClient) {
            wsClient.sendTypingStart(parseInt(incoming_id));
            
            // Clear previous timeout
            clearTimeout(typingTimeout);
            
            // Stop typing after 2 seconds of inactivity
            typingTimeout = setTimeout(() => {
                wsClient.sendTypingStop(parseInt(incoming_id));
            }, 2000);
        }
    }else{
        sendBtn.classList.remove("active");
        if (wsClient) {
            wsClient.sendTypingStop(parseInt(incoming_id));
        }
    }
}

// Send message via WebSocket
sendBtn.onclick = ()=>{
    const message = inputField.value.trim();
    if (message && wsClient) {
        wsClient.sendMessage(parseInt(incoming_id), message);
        inputField.value = "";
        sendBtn.classList.remove("active");
        
        // Stop typing indicator
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }
        wsClient.sendTypingStop(parseInt(incoming_id));
    }
}

chatBox.onmouseenter = ()=>{
    chatBox.classList.add("active");
}

chatBox.onmouseleave = ()=>{
    chatBox.classList.remove("active");
}

function initWebSocket() {
    if (!currentUserId) {
        console.error('User ID not available');
        return;
    }
    
    // Create WebSocket client
    wsClient = new WebSocketClient(currentUserId);
    
    // Handle new messages
    wsClient.on('new_message', (data) => {
        // Only show if it's for this conversation
        if ((data.outgoing_msg_id == incoming_id && data.incoming_msg_id == currentUserId) ||
            (data.outgoing_msg_id == currentUserId && data.incoming_msg_id == incoming_id)) {
            appendMessage(data);
            
            // Send read receipt if we received the message
            if (data.incoming_msg_id == currentUserId) {
                wsClient.sendReadReceipt(data.msg_id, data.outgoing_msg_id);
            }
        }
    });
    
    // Handle message sent confirmation
    wsClient.on('message_sent', (data) => {
        console.log('Message sent successfully:', data);
    });
    
    // Handle typing indicator
    wsClient.on('typing_indicator', (data) => {
        if (data.user_id == incoming_id) {
            showTypingIndicator(data.status === 'typing');
        }
    });
    
    // Handle user status changes
    wsClient.on('user_status', (data) => {
        if (data.user_id == incoming_id) {
            updateUserStatus(data.status);
        }
    });
    
    // Load initial messages (fallback to AJAX)
    loadMessages();
}

function loadMessages() {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "php/get-chat.php", true);
    xhr.onload = ()=>{
      if(xhr.readyState === XMLHttpRequest.DONE){
          if(xhr.status === 200){
            let data = xhr.response;
            chatBox.innerHTML = data;
            scrollToBottom();
          }
      }
    }
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send("incoming_id="+incoming_id);
}

function appendMessage(data) {
    const isOutgoing = data.outgoing_msg_id == currentUserId;
    const messageHtml = isOutgoing ? 
        `<div class="chat outgoing">
            <div class="details">
                <p>${escapeHtml(data.message)}</p>
            </div>
        </div>` :
        `<div class="chat incoming">
            <img src="php/images/${data.sender.img}" alt="">
            <div class="details">
                <p>${escapeHtml(data.message)}</p>
            </div>
        </div>`;
    
    chatBox.insertAdjacentHTML('beforeend', messageHtml);
    
    if(!chatBox.classList.contains("active")){
        scrollToBottom();
    }
}

function showTypingIndicator(show) {
    const existingIndicator = document.querySelector('.typing-indicator');
    
    if (show && !existingIndicator) {
        const indicatorHtml = `<div class="typing-indicator">
            <span></span><span></span><span></span>
        </div>`;
        chatBox.insertAdjacentHTML('beforeend', indicatorHtml);
        scrollToBottom();
    } else if (!show && existingIndicator) {
        existingIndicator.remove();
    }
}

function updateUserStatus(status) {
    const statusElement = document.querySelector('.chat-area header .details p');
    if (statusElement) {
        statusElement.textContent = status === 'online' ? 'Active now' : 'Offline';
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function scrollToBottom(){
    chatBox.scrollTop = chatBox.scrollHeight;
}

function initFileUpload() {
    fileUploadHandler = new FileUploadHandler({
        conversationWith: parseInt(incoming_id),
        onUploadStart: (fileName) => {
            showUploadProgress(fileName, 0);
        },
        onUploadProgress: (percent, fileName) => {
            updateUploadProgress(fileName, percent);
        },
        onUploadComplete: (response, file) => {
            hideUploadProgress();
            // Display uploaded file in chat
            displayFileMessage(response);
            // Send via WebSocket to notify recipient
            if (wsClient) {
                wsClient.send('file_uploaded', {
                    incoming_id: parseInt(incoming_id),
                    file_data: response
                });
            }
        },
        onUploadError: (error) => {
            hideUploadProgress();
            alert('Upload error: ' + error);
        }
    });
}

function showUploadProgress(fileName, percent) {
    let progressDiv = document.querySelector('.upload-progress');
    if (!progressDiv) {
        progressDiv = document.createElement('div');
        progressDiv.className = 'upload-progress';
        progressDiv.innerHTML = `
            <h4>Uploading: <span class="file-name">${fileName}</span></h4>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width: ${percent}%"></div>
            </div>
        `;
        document.body.appendChild(progressDiv);
    }
}

function updateUploadProgress(fileName, percent) {
    const progressDiv = document.querySelector('.upload-progress');
    if (progressDiv) {
        progressDiv.querySelector('.file-name').textContent = fileName;
        progressDiv.querySelector('.progress-bar-fill').style.width = percent + '%';
    }
}

function hideUploadProgress() {
    const progressDiv = document.querySelector('.upload-progress');
    if (progressDiv) {
        setTimeout(() => progressDiv.remove(), 1000);
    }
}

function displayFileMessage(fileData) {
    let fileHtml = '';
    
    if (fileData.file_type === 'image') {
        fileHtml = `
            <div class="file-attachment">
                <img src="${fileData.file_url}" alt="Uploaded image" 
                     onclick="openImageLightbox('${fileData.file_url}')">
            </div>
        `;
    } else if (fileData.file_type === 'video') {
        fileHtml = `
            <div class="file-attachment">
                <video controls>
                    <source src="${fileData.file_url}" type="video/mp4">
                </video>
            </div>
        `;
    } else if (fileData.file_type === 'audio') {
        fileHtml = `
            <div class="file-attachment">
                <audio controls>
                    <source src="${fileData.file_url}">
                </audio>
            </div>
        `;
    } else {
        fileHtml = `
            <a href="api/v1/files/download.php?file=${encodeURIComponent(fileData.file_url)}" 
               class="file-document" target="_blank">
                <span class="file-icon">📄</span>
                <div class="file-info">
                    <div class="name">${fileData.original_name}</div>
                    <div class="size">${formatFileSize(fileData.file_size)}</div>
                </div>
            </a>
        `;
    }
    
    const messageHtml = `
        <div class="chat outgoing">
            <div class="details">
                ${fileHtml}
            </div>
        </div>
    `;
    
    chatBox.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function openImageLightbox(imageUrl) {
    const lightbox = document.createElement('div');
    lightbox.className = 'image-lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <button class="close-lightbox">&times;</button>
            <img src="${imageUrl}" alt="Full size image">
        </div>
    `;
    
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox || e.target.className === 'close-lightbox') {
            lightbox.remove();
        }
    });
    
    document.body.appendChild(lightbox);
}

  