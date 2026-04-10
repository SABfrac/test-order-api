<?php

namespace service;
use config\Rabbitmq\Publisher;
use repository\OrderRepository;
use repository\ProductRepository;



class OrderService {
    public function __construct(
        private \PDO $pdo,
        private ProductRepository $productsRepo,
        private OrderRepository $orders,
        private ProductService $productService,
        private Publisher $publisher
    ) {}

    public function createOrder(int $productId, int $qty): array {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("quantity must be > 0");
        }

        $this->pdo->beginTransaction();
        try {
            $product = $this->productsRepo->getForUpdate($productId);
            if (!$product) {
                throw new \RuntimeException("product not found");
            }

            $ok = $this->productService->decreaseStock($productId, $qty);
            if (!$ok) {
                throw new \RuntimeException("not enough stock");
            }


            $total = bcmul((string)$product['price'], (string)$qty, 2);
            $orderId = $this->orders->create($productId, $qty, $total, 'created');

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }


        $this->productService->invalidateListCache();

        //  Публикуем событие (после коммита)
        //  если publishOrderCreated упадёт — заказ уже создан.
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