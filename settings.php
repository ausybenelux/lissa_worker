<?php

$conf = array(
  'amqp' => array(
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'password' => 'guest',
  ),
  'publisher' => array(
    'host' => 'localhost',
    'endpoint' => 'publish',
    'port' => '8080',
  ),
);

if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
