<?php
require 'vendor/autoload.php';
require 'api/gateway.php';

use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use ChatCafe\Gateway;

$ws = new WsServer(new Gateway);
$server = IoServer::factory(new HttpServer($ws), 8080);
$server->run();

?>