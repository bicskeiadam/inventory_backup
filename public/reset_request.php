<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Mailer.php';

$method = strtolower($_SERVER['REQUEST_METHOD']);

$db = new Database();
$pdo = $db->getConnection();

switch ($method) {

    case "get":
        // Reset kérő form megjelenítése
        include_once "reset_request_form.php";
        break;

    case "post":

        if (isset($_POST['resetEmail'])) {
            $resetEmail = trim($_POST['resetEmail']);
        }

        if (empty($resetEmail)) {
            redirection('reset_form_message.php?rf=8'); // Email empty
        }

        // Email létezik?
        $sql = "SELECT id, is_active FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":email" => $resetEmail]);

        if ($stmt->rowCount() == 0) {
            redirection('reset_form_message.php?rf=14'); // Email not found
        }

        $user = $stmt->fetch();

        if ($user['is_active'] != 1) {
            redirection('reset_form_message.php?rf=13'); // Inactive account
        }

        // Token készítése (40 karakter hex)
        $token = bin2hex(random_bytes(20));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // DB update
        $sql = "UPDATE users 
                SET reset_token = :token,
                    token_expires = :expires
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":token" => $token,
            ":expires" => $expires,
            ":id" => $user['id']
        ]);

        if ($stmt->rowCount() == 0) {
            redirection('./reset_form_message.php?rf=15'); // Could not update
        }

        // Reset email link összeállítása
        $resetLink = APP_URL . "/inventory_backup/public/reset_password.php?token=" . $token;

        $subject = "Password Reset Request";
        $body = "
            <p>You requested a password reset.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>If you did not request this, ignore this email.</p>
        ";

        // Email küldés
        Mailer::send($resetEmail, $subject, $body);

        redirection('./reset_form_message.php?rf=12'); // Reset email sent
        break;
}
