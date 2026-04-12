<?php

use interfaces\ProductProvider;
use config\RedisCache;
use service\ProductService;
use interfaces\StockObserver;


/**
* Декоратор для кэширования данных
 *
 * Реализует паттерн "Декоратор" для прозрачного добавления кэша к любому ProductProvider
* и паттерн "Observer" (StockObserver) для автоматической инвалидации кэша.
 */

class CachingProductProvider implements ProductProvider,StockObserver
{
    private const CACHE_KEY = 'products_list';
    private const TTL = 30;
    public function __construct(
        private ProductService $innerService,
        private RedisCache $cache
    ) {}

    /**
     * Пробрасываем вызов в основной сервис.
     * Сама инвалидация кэша произойдет в методе onStockChanged,
     * который вызовет ProductService через механизм Observer.
     */
    public function decreaseStock(int $productId, int $qty): bool {
        return $this->innerService->decreaseStock($productId, $qty);
    }


    public function listProducts(): array {
        $cached = $this->cache->getJson(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->innerService->listProducts();
        $this->cache->setJson(self::CACHE_KEY, $data, self::TTL);
        return $data;
    }

    public function onStockChanged(): void {
        $this->cache->del(self::CACHE_KEY);
    }

}