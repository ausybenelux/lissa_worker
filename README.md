# LISSA Worker

The LISSA Worker script listens to the RabbitMQ server for notifications. It
will parse and forward the incoming notifications to the Nginx push stream
server's publish endpoint.

## Configuration

You can either edit the settings.php file or create your own local.settings.php
file that overrides the default settings.

```php
$conf = array(
  // The AMQP server to read the LISSA notifications from.
  'amqp' => array(
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'password' => 'guest',
  ),
  // An Nginx push stream server to forward notifications to.
  'publisher' => array(
    'host' => 'localhost',
    'endpoint' => 'publish',
    'port' => '8080',
  ),
);
```

## Usage

The worker script should listen continuously for new messages. It is recommended
you run the script using a process manager like supervisord.

The [LISSA infrastructure repo](https://github.com/crosscheck/lissa_infrastructure)
contains a chef recipe that can provision a server with LISSA worker and
supervisord.