<?php
/**
 * @file
 * Worker script for LISSA to pass data from the message bus to the push stream.
 *
 * This file should run as a daemon or service (e.g. using supervisor) so it can
 * continuously pass data from the message bug to the nginx push stream server.
 */

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;

$connection = new AMQPConnection($conf['amqp']['host'], $conf['amqp']['port'], $conf['amqp']['user'], $conf['amqp']['password']);
$channel = $connection->channel();
$channel->queue_declare('content_notification', false, false, false, false);
$uri = $conf['publisher']['host'] . '/' . $conf['publisher']['endpoint'];


/**
 * Sends a notification to the push stream server.
 *
 * @param string $channel
 *   The channel to publish the notification to.
 * @param array $data
 *   The data to publish.
 * @param string $uri
 *   The URL to published to.
 * @param string $port
 *   The server port.
 */
function lissa_worker_send_notification($channel, $data, $uri, $port = '80') {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, $uri . '?channel=' . $channel);
  curl_setopt($ch, CURLOPT_PORT, $port);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $ret = curl_exec($ch);
  curl_close($ch);
  echo "\n", ' [*] Sending message:', "\n";
  echo "Channel: " . $channel, "\n";
  echo "Data: ", "\n", print_r($data, TRUE), "\n";
  echo "Result: ", $ret, "\n\n";
}

$callback = function($message) {
  global $uri, $conf;
  static $id;

  if (isset($message->body)) {
    $notification = json_decode($message->body);


    if (isset($notification->api_meta->event_uuid)) {
      $id = empty($id) ? 1 : $id++;
      // Use one channel per event.
      $channel = $notification->api_meta->event_uuid;
      $action = isset($notification->api_meta->type) ? $notification->api_meta->type : 'create';
      $post_data = json_encode(array(
        'text' => $notification,
        'tag' => $action,
        'id' => $id,
        'channel' => $notification->api_meta->event_uuid,
      ));

      lissa_worker_send_notification($channel, $post_data, $uri, $conf['publisher']['port']);
    }
  }
};

echo ' [*] Subscribed to ', $conf['amqp']['host'], "\n";
echo ' [*] Publishing to ', $uri, "\n";
echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$channel->basic_consume('content_notification', '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
  $channel->wait();
}

$channel->close();
$connection->close();
