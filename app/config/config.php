<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
define('MAIL_HOST', $_ENV['MAIL_HOST']);
define('MAIL_USER', $_ENV['MAIL_USER']);
define('MAIL_PASS', $_ENV['MAIL_PASS']);
define('MAIL_PORT', $_ENV['MAIL_PORT']);
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION']);
