<?php
/**
 * WebSocket Server Configuration
 */

return [
    'host' => '0.0.0.0',
    'port' => 8080,
    
    // Database configuration (inherited from main app)
    'db' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'chatapp'
    ],
    
    // WebSocket settings
    'heartbeat_interval' => 30, // seconds
    'connection_timeout' => 300, // seconds (5 minutes)
    
    // Security
    'allowed_origins' => ['http://localhost', 'http://127.0.0.1'], // Update for production with actual domain
    
    // Logging
    'debug' => true,
    'log_file' => __DIR__ . '/websocket.log'
];
