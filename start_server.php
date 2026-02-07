<?php
// Start the PHP built-in server
$host = '127.0.0.1';
$port = '8000';
$command = sprintf('php -S %s:%s', $host, $port);
echo "Starting server at http://$host:$port\n";
echo "Press Ctrl+C to stop.\n";
passthru($command);
?>