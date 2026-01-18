<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Chat;

// Load configuration
$config = require __DIR__ . '/config.php';

// Database connection
$db = new mysqli(
    $config['db']['host'],
    $config['db']['username'],
    $config['db']['password'],
    $config['db']['database']
);

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

echo "Starting WebSocket Server...\n";
echo "Host: {$config['host']}\n";
echo "Port: {$config['port']}\n";
echo "Database: {$config['db']['database']}\n";
echo "--------------------------------\n";

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($db)
        )
    ),
    $config['port'],
    $config['host']
);

echo "WebSocket server is running!\n";
echo "Connect to: ws://{$config['host']}:{$config['port']}\n";
echo "Press Ctrl+C to stop the server\n";
echo "--------------------------------\n";

$server->run();
