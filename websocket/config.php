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
    'allowed_origins' => ['*'], // Set specific origins in production
    
    // Logging
    'debug' => true,
    'log_file' => __DIR__ . '/websocket.log'
];
