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
    });

let wsClient = null;
let typingTimeout = null;

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
  