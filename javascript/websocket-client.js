/**
 * WebSocket Client for Real-time Chat Communication
 */

class WebSocketClient {
    constructor(userId) {
        this.userId = userId;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.reconnectDelay = 1000; // Start with 1 second
        this.heartbeatInterval = null;
        this.isConnected = false;
        this.messageQueue = [];
        this.eventHandlers = {};
        
        this.connect();
    }
    
    connect() {
        if (this.ws && (this.ws.readyState === WebSocket.CONNECTING || this.ws.readyState === WebSocket.OPEN)) {
            console.log('WebSocket already connected or connecting');
            return;
        }
        
        const wsHost = window.location.hostname;
        const wsPort = 8080;
        // In production, use session_id or a generated token
        const sessionToken = 'temp_token'; // This should be fetched from backend or generated
        const wsUrl = `ws://${wsHost}:${wsPort}?user_id=${this.userId}&session_token=${sessionToken}`;
        
        console.log(`Connecting to WebSocket: ${wsUrl}`);
        this.updateConnectionStatus('connecting');
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = (event) => {
                console.log('WebSocket connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.reconnectDelay = 1000;
                this.updateConnectionStatus('connected');
                
                // Start heartbeat
                this.startHeartbeat();
                
                // Send queued messages
                this.flushMessageQueue();
                
                // Trigger custom handlers
                this.trigger('open', event);
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                console.log('WebSocket message received:', data);
                
                // Route to appropriate handler
                this.handleMessage(data);
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.updateConnectionStatus('error');
                this.trigger('error', error);
            };
            
            this.ws.onclose = (event) => {
                console.log('WebSocket disconnected');
                this.isConnected = false;
                this.updateConnectionStatus('disconnected');
                this.stopHeartbeat();
                
                // Attempt to reconnect
                this.reconnect();
                
                this.trigger('close', event);
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.reconnect();
        }
    }
    
    reconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.updateConnectionStatus('failed');
            return;
        }
        
        this.reconnectAttempts++;
        // Exponential backoff with max 30 seconds
        const delay = Math.min(this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1), 30000);
        
        console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        this.updateConnectionStatus('reconnecting');
        
        setTimeout(() => {
            this.connect();
        }, delay);
    }
    
    send(type, data) {
        const message = {
            type: type,
            ...data
        };
        
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(message));
        } else {
            // Queue message for later
            console.log('WebSocket not connected, queueing message');
            this.messageQueue.push(message);
        }
    }
    
    flushMessageQueue() {
        if (this.messageQueue.length > 0) {
            console.log(`Sending ${this.messageQueue.length} queued messages`);
            this.messageQueue.forEach(message => {
                this.ws.send(JSON.stringify(message));
            });
            this.messageQueue = [];
        }
    }
    
    handleMessage(data) {
        switch (data.type) {
            case 'new_message':
                this.trigger('new_message', data);
                break;
            case 'message_sent':
                this.trigger('message_sent', data);
                break;
            case 'typing_indicator':
                this.trigger('typing_indicator', data);
                break;
            case 'user_status':
                this.trigger('user_status', data);
                break;
            case 'read_receipt':
                this.trigger('read_receipt', data);
                break;
            case 'heartbeat':
                // Heartbeat response received
                break;
            default:
                console.log('Unknown message type:', data.type);
        }
    }
    
    // Event handler registration
    on(event, callback) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(callback);
    }
    
    trigger(event, data) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].forEach(callback => {
                callback(data);
            });
        }
    }
    
    // Heartbeat to keep connection alive
    startHeartbeat() {
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected) {
                this.send('heartbeat', {});
            }
        }, 30000); // Every 30 seconds
    }
    
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }
    
    // Update connection status indicator
    updateConnectionStatus(status) {
        const indicator = document.querySelector('.connection-status');
        if (indicator) {
            indicator.className = 'connection-status ' + status;
            
            let statusText = '';
            switch (status) {
                case 'connecting':
                    statusText = 'Connecting...';
                    break;
                case 'connected':
                    statusText = 'Connected';
                    break;
                case 'disconnected':
                    statusText = 'Disconnected';
                    break;
                case 'reconnecting':
                    statusText = 'Reconnecting...';
                    break;
                case 'error':
                    statusText = 'Connection Error';
                    break;
                case 'failed':
                    statusText = 'Connection Failed';
                    break;
            }
            
            indicator.textContent = statusText;
        }
    }
    
    // Public API methods
    sendMessage(incomingId, message) {
        this.send('message', {
            incoming_id: incomingId,
            message: message
        });
    }
    
    sendTypingStart(toUserId) {
        this.send('typing_start', {
            to_user_id: toUserId
        });
    }
    
    sendTypingStop(toUserId) {
        this.send('typing_stop', {
            to_user_id: toUserId
        });
    }
    
    sendReadReceipt(msgId, conversationWith) {
        this.send('read_receipt', {
            msg_id: msgId,
            conversation_with: conversationWith
        });
    }
    
    disconnect() {
        if (this.ws) {
            this.stopHeartbeat();
            this.ws.close();
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketClient;
}
