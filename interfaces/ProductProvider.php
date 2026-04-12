<?php
namespace interfaces;

interface ProductProvider {


    public function listProducts(): array;


    /**
     * Уменьшает количество товара на складе.
     *
     * @param int $productId
     * @param int $qty
     * @return bool
     */
    public function decreaseStock(int $productId, int $qty): bool;
}
