<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

$method = strtolower($_SERVER['REQUEST_METHOD']);
$db = new Database();
$pdo = $db->getConnection();
// Token GET vagy POST-ból
$token = "";
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
}
if (isset($_POST['token'])) {
    $token = trim($_POST['token']);
}

switch ($method) {

    // ----------- GET: Megnyitás a linkből -----------
    case "get":
        if (!empty($token) && strlen($token) === 40) {

            $sql = "SELECT id FROM users 
                    WHERE BINARY reset_token = :token 
                    AND token_expires > NOW() 
                    AND is_active = 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([":token" => $token]);

            if ($stmt->rowCount() > 0) {
                include_once "reset_form.php"; // form mely POST-ol
            } else {
                redirection('reset_form_message.php?rf=15'); // Token invalid/expired
            }
        } else {
            redirection('reset_form_message.php?rf=0'); // Wrong token format
        }
        break;


    // ----------- POST: Új jelszó elküldve -----------
    case "post":

        if (!empty($token) && strlen($token) === 40) {

            // Email
            if (isset($_POST['resetEmail'])) {
                $resetEmail = trim($_POST["resetEmail"]);
            }

            // Password
            if (isset($_POST['resetPassword'])) {
                $resetPassword = trim($_POST["resetPassword"]);
            }

            // Password confirm
            if (isset($_POST['resetPasswordConfirm'])) {
                $resetPasswordConfirm = trim($_POST["resetPasswordConfirm"]);
            }

            if (empty($resetEmail)) {
                redirection('reset_form.php?rf=8');
            }

            if (empty($resetPassword)) {
                redirection('reset_form.php?rf=9');
            }

            // Password policy
            if (!preg_match("#.*^(?=.{8,20})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*\W).*$#", $resetPassword)) {
                redirection('reset_form.php?rf=10');
            }

            if (empty($resetPasswordConfirm)) {
                redirection('reset_form.php?rf=9');
            }

            if ($resetPassword !== $resetPasswordConfirm) {
                redirection('reset_form.php?rf=7');
            }

            // Hash
            $passwordHashed = password_hash($resetPassword, PASSWORD_DEFAULT);

            // Update
            $sql = "UPDATE users 
                    SET reset_token = '', 
                        token_expires = NULL, 
                        password = :newPassword
                    WHERE BINARY reset_token = :token 
                    AND token_expires > NOW() 
                    AND is_active = 1 
                    AND email = :email";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":newPassword" => $passwordHashed,
                ":token"       => $token,
                ":email"       => $resetEmail
            ]);

            if ($stmt->rowCount() > 0) {
                redirection('reset_form_message.php?rf=16'); // Successful reset
            } else {
                redirection('reset_form_message.php?rf=15'); // Failed update
            }

        } else {
            redirection('reset_form_message.php?rf=0'); // Invalid token
        }

        break;
}
