<?php

namespace service;
use repository\OrderRepository;
use repository\ProductRepository;
use interfaces\ProductProvider;


/**
 * Сервис управления заказами.
 *
 * Реализует  создание заказа, контролирует целостность данных
 * через транзакции БД и взаимодействие с товарным провайдером и публикацию событий.
 */
class OrderService {
    public function __construct(
        private \PDO $pdo,
        private ProductRepository $productsRepo,
        private OrderRepository $orders,
        private ProductProvider $productProvider,
        private Publisher $publisher
    ) {}

    /**
     * Создает заказ на покупку товара.
     *
     * Алгоритм работы:
     *  1. Валидация входных данных.
     *  2. Старт транзакции.
     *  3. Блокировка строки товара (FOR UPDATE) для предотвращения race conditions.
     *  4. Списание остатка через ProductProvider (автоматически вызывает сброс кэша через Observer).
     *  5. Регистрация заказа в базе данных.
     *  6. Фиксация (commit) транзакции.
     *  7. Отправка уведомления в  RabbitMQ вне транзакции.
     *
     *
     * @return [order_id => int, status => string, total_price => string] .
     *
     * @throws \InvalidArgumentException Если передано некорректное количество.
     * @throws \RuntimeException         Если продукт не найден или недостаточно товара на складе.
     * @throws \Throwable                При критических ошибках БД (транзакция откатывается).
     */
    public function createOrder(int $productId, int $qty): array {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("quantity must be > 0");
        }

        $this->pdo->beginTransaction();
        try {
            $product = $this->productsRepo->getForUpdate($productId);
            if (!$product) {
                throw new \RuntimeException("товар не найден");
            }

            $ok = $this->productProvider->decreaseStock($productId, $qty);
            if (!$ok) {
                throw new \RuntimeException("недостаточно на складе");
            }


            $total = bcmul((string)$product['price'], (string)$qty, 2);
            $orderId = $this->orders->create($productId, $qty, $total, 'created');

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }


        try {
            $this->publisher->publishOrderCreated([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $qty,
                'total_price' => $total,
                'status' => 'created',
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

        } catch (\Throwable $e) {

            error_log("RabbitMQ Error: " . $e->getMessage());
        }
        return [
            'order_id' => $orderId,
            'status' => 'created',
            'total_price' => $total
        ];
    }


}