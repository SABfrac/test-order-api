<?php
namespace service;

use repository\ProductRepository;
use config\RedisCache;
use interfaces\StockObserver;
use interfaces\ProductProvider;

/**
 * Сервис для управления продуктами и их кэшированием.
 * Отвечает за координацию действий между БД и Redis.
 */

class ProductService implements ProductProvider {
    private array $observers = [];

    public function __construct(
        private ProductRepository $productRepo) {}

    public function addObserver(StockObserver $observer): void {
        $this->observers[] = $observer;
    }


    /**
     * Уменьшает количество товара и сбрасывает кэш.
     */

    public function decreaseStock(int $productId, int $qty): bool
    {
        $ok = $this->productRepo->decrementStock($productId, $qty);
        if ($ok) {
            foreach ($this->observers as $observer) {
                $observer->onStockChanged();
            }
        }
        return $ok;
    }

    /**
     * Возвращает список всех продуктов.
     *
     * Сначала  из кеша если данных нет из БД,
     *
     * @return array
     */
    public function listProducts(): array {

        return $this->productRepo->listAll();
    }


}
