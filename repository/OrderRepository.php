<?php
namespace repository;

/**
 * Отвечает за сохранение данных о заказах в БД.
 */

class OrderRepository {
    public function __construct(private \PDO $pdo) {}

    /**
     * Создает новую запись о заказе в БД.
     * @return int               ID созданного заказа.
     * @throws \PDOException     Выбрасывает исключение при ошибке записи в БД.
     */
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