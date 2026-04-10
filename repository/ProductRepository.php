<?php

namespace repository;


class ProductRepository
{
    public function __construct(private \PDO $pdo) {}

    public function listAll(): array
    {
        return $this->pdo->query("SELECT id, name, price, stock FROM products ORDER BY id")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getForUpdate(int $productId): ?array
    {
        $st = $this->pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = :id FOR UPDATE");
        $st->execute([':id' => $productId]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function decrementStock(int $productId, int $qty): bool
    {
        // Дополнительная защита от race conditions: уменьшение только если хватает stock.
        $st = $this->pdo->prepare("
            UPDATE products
            SET stock = stock - :qty
            WHERE id = :id AND stock >= :qty
        ");
        $st->execute([':id' => $productId, ':qty' => $qty]);
        return $st->rowCount() === 1;
    }


}