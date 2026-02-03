<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?status=error');
    exit;
}

$subject = "Új üzenet a kapcsolat űrlapról";
$body = "<p><strong>Név:</strong> " . htmlspecialchars($name) . "</p>";
$body .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
$body .= "<p><strong>Üzenet:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

$to = MAIL_USER; // send to configured email

$sent = Mailer::send($to, $subject, $body);

if ($sent) {
    header('Location: index.php?status=sent');
    exit;
} else {
    header('Location: index.php?status=error');
    exit;
}

