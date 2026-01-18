<?php
namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $db;
    
    public function __construct($db) {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        $this->db = $db;
        echo "WebSocket Chat Server Started\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        
        // Parse query string to get user authentication
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $query);
        
        if (isset($query['user_id'])) {
            $userId = (int)$query['user_id'];
            $conn->userId = $userId;
            $this->users[$userId] = $conn;
            
            // Update user status to online
            $this->updateUserStatus($userId, 'Active now');
            
            // Broadcast online status to all connected users
            $this->broadcastUserStatus($userId, 'online');
            
            echo "User {$userId} connected. Total connections: " . count($this->clients) . "\n";
        } else {
            echo "Connection from {$conn->resourceId} (unauthenticated)\n";
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }
        
        // Route to appropriate handler based on message type
        switch ($data['type']) {
            case 'message':
                $this->handleNewMessage($from, $data);
                break;
            case 'typing_start':
                $this->handleTypingStart($from, $data);
                break;
            case 'typing_stop':
                $this->handleTypingStop($from, $data);
                break;
            case 'read_receipt':
                $this->handleReadReceipt($from, $data);
                break;
            case 'heartbeat':
                $this->handleHeartbeat($from);
                break;
            default:
                echo "Unknown message type: {$data['type']}\n";
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        // Remove the connection
        $this->clients->detach($conn);
        
        if (isset($conn->userId)) {
            $userId = $conn->userId;
            unset($this->users[$userId]);
            
            // Update user status to offline with last seen
            $this->updateUserStatus($userId, 'Offline');
            
            // Broadcast offline status
            $this->broadcastUserStatus($userId, 'offline');
            
            echo "User {$userId} disconnected. Total connections: " . count($this->clients) . "\n";
        } else {
            echo "Connection {$conn->resourceId} closed\n";
        }
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    // Handler methods
    
    private function handleNewMessage(ConnectionInterface $from, $data) {
        if (!isset($data['incoming_id'], $data['message'])) {
            return;
        }
        
        $outgoingId = $from->userId;
        $incomingId = (int)$data['incoming_id'];
        $message = $data['message'];
        
        // Save message to database
        $stmt = $this->db->prepare("INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $incomingId, $outgoingId, $message);
        $stmt->execute();
        $msgId = $this->db->insert_id;
        $stmt->close();
        
        // Get sender info
        $stmt = $this->db->prepare("SELECT unique_id, fname, lname, img FROM users WHERE unique_id = ?");
        $stmt->bind_param("i", $outgoingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $senderInfo = $result->fetch_assoc();
        $stmt->close();
        
        // Prepare message data to send
        $messageData = [
            'type' => 'new_message',
            'msg_id' => $msgId,
            'outgoing_msg_id' => $outgoingId,
            'incoming_msg_id' => $incomingId,
            'message' => htmlspecialchars($message),
            'sender' => $senderInfo,
            'timestamp' => time()
        ];
        
        // Send to recipient if online
        if (isset($this->users[$incomingId])) {
            $this->users[$incomingId]->send(json_encode($messageData));
        }
        
        // Send confirmation to sender
        $from->send(json_encode([
            'type' => 'message_sent',
            'msg_id' => $msgId,
            'status' => 'delivered'
        ]));
        
        echo "Message {$msgId} sent from {$outgoingId} to {$incomingId}\n";
    }
    
    private function handleTypingStart(ConnectionInterface $from, $data) {
        if (!isset($data['to_user_id'])) {
            return;
        }
        
        $toUserId = (int)$data['to_user_id'];
        
        if (isset($this->users[$toUserId])) {
            $this->users[$toUserId]->send(json_encode([
                'type' => 'typing_indicator',
                'user_id' => $from->userId,
                'status' => 'typing'
            ]));
        }
    }
    
    private function handleTypingStop(ConnectionInterface $from, $data) {
        if (!isset($data['to_user_id'])) {
            return;
        }
        
        $toUserId = (int)$data['to_user_id'];
        
        if (isset($this->users[$toUserId])) {
            $this->users[$toUserId]->send(json_encode([
                'type' => 'typing_indicator',
                'user_id' => $from->userId,
                'status' => 'stopped'
            ]));
        }
    }
    
    private function handleReadReceipt(ConnectionInterface $from, $data) {
        if (!isset($data['msg_id'], $data['conversation_with'])) {
            return;
        }
        
        $msgId = (int)$data['msg_id'];
        $conversationWith = (int)$data['conversation_with'];
        
        // Send read receipt to the sender
        if (isset($this->users[$conversationWith])) {
            $this->users[$conversationWith]->send(json_encode([
                'type' => 'read_receipt',
                'msg_id' => $msgId,
                'read_by' => $from->userId,
                'timestamp' => time()
            ]));
        }
    }
    
    private function handleHeartbeat(ConnectionInterface $from) {
        $from->send(json_encode([
            'type' => 'heartbeat',
            'timestamp' => time()
        ]));
    }
    
    // Helper methods
    
    private function updateUserStatus($userId, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE unique_id = ?");
        $stmt->bind_param("si", $status, $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    private function broadcastUserStatus($userId, $status) {
        $statusData = json_encode([
            'type' => 'user_status',
            'user_id' => $userId,
            'status' => $status,
            'timestamp' => time()
        ]);
        
        foreach ($this->clients as $client) {
            if (isset($client->userId) && $client->userId != $userId) {
                $client->send($statusData);
            }
        }
    }
}
