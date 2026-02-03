<?php
class Database {
    private ?PDO $pdo = null;

    public function getConnection(): PDO {
        if ($this->pdo) return $this->pdo;
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $this->pdo;
    }
}

function redirection(string $url): void
{
    header("Location: $url");
    exit();
}

