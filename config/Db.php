<?php
namespace config;
 class Db {
    public function __construct(private \PDO $pdo) {}

    public function pdo(): \PDO { return $this->pdo; }
}