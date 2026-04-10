<?php
namespace repository;

class OrderRepository {
    public function __construct(private \PDO $pdo) {}

    public function create(int $productId, int $qty, string $totalPrice, string $status): int {
        $st = $this->pdo->prepare("
            INSERT INTO orders(product_id, quantity, total_price, status)
            VALUES (:pid, :qty, :total, :status)
        ");
        $st->execute([
            ':pid' => $productId,
            ':qty' => $qty,
            ':total' => $totalPrice,
            ':status' => $status,
        ]);
        return (int)$this->pdo->lastInsertId();
    }


}