<?php
namespace EntityORM;

use PDO;
use PDOStatement;

abstract class DBContext
{
    public PDO $pdo;

    public mixed $lastInsertId { 
        get => $this->pdo->lastInsertId();
    }

    public function __construct(string $dsn, ?string $username = null, ?string $password = null)
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }

    public function run(string $sql)
    {
        $this->pdo->exec($sql);
    }

    public function execute(string $sql, array $params): PDOStatement
    {
        $sttmt = $this->pdo->prepare($sql);
        $sttmt->execute($params);
        return $sttmt;
    }
}
