<?php
namespace service;

use repository\ProductRepository;
use config\RedisCache;

/**
 * Сервис для управления продуктами и их кэшированием.
 * Отвечает за координацию действий между БД и Redis.
 */

class ProductService {
    private const CACHE_KEY = 'products_list';
    private const TTL = 30;

    public function __construct(
        private ProductRepository $productRepo,
        private RedisCache $cache
    ) {}


    /**
     * Уменьшает количество товара и сбрасывает кэш.
     */

    public function decreaseStock(int $productId, int $qty): bool
    {
        $ok = $this->productRepo->decrementStock($productId, $qty);
        if ($ok) {
            $this->invalidateListCache();
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
        $cached = $this->cache->getJson(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }
        $rows = $this->productRepo->listAll();
        $this->cache->setJson(self::CACHE_KEY, $rows, self::TTL);
        return $rows;
    }

    public function invalidateListCache(): void
    {
        $this->cache->del(self::CACHE_KEY);
    }
}
