<?php
namespace interfaces;
interface StockObserver {
    public function onStockChanged(): void;
}
