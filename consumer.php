<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPMailer\PHPMailer\PHPMailer;

// Configurer la connexion à RabbitMQ
$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Déclarer la file d'attente
$channel->queue_declare('confirmation_email_queue', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    $data = json_decode($msg->body, true);

    // Récupérer l'e-mail de l'utilisateur depuis le message RabbitMQ
    $to = $data['email'];
    $subject = $data['subject'];
    $message = $data['message'];

    // Configurer l'envoi d'e-mails avec PHPMailer
    $mailer = new PHPMailer();
    $mailer->isSMTP();
    $mailer->Host = 'localhost'; // Adresse IP de votre machine hôte ou adresse IP du conteneur Docker si vous exécutez le code dans un autre conteneur
    $mailer->Port = 1025; // Port SMTP de Mailhog
    $mailer->SMTPAuth = false;

    // Configurer le contenu de l'e-mail
    $mailer->setFrom('no-reply@email.com', 'No Reply');
    $mailer->addAddress($to);
    $mailer->Subject = $subject;
    $mailer->Body = $message;

    // Envoyer l'e-mail
    if ($mailer->send()) {
        echo " [x] Sent email to $to\n";
    } else {
        echo " [x] Error sending email to $to: " . $mailer->ErrorInfo . "\n";
    }
};

$channel->basic_consume('confirmation_email_queue', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
