<?php

use config\Db;
use config\Rabbitmq\Connection;
use config\RedisCache;
use repository\OrderRepository;
use repository\ProductRepository;
use service\OrderService;
use service\ProductService;
use service\Publisher;



require __DIR__ . '/vendor/autoload.php';


header('Content-Type: application/json; charset=utf-8');

//1. Инфраструктура
$pdo = new PDO('mysql:host=db;dbname=app;charset=utf8mb4', 'app', 'app');
$redis = new Redis();
$redis->connect('redis', 6379);

// 2. Репозитории (низкоуровневая работа с БД)
$productRepo = new ProductRepository($pdo);
$orderRepo = new OrderRepository($pdo);
$cache = new RedisCache($redis);

// 3. События (RabbitMQ)
$rabbit = new Connection('rabbitmq', 5672, 'guest', 'guest');
$publisher = new Publisher(
    $rabbit,
    Connection::EXCHANGE_ORDERS,
    Connection::ROUTING_ORDER_CREATED
);

// 4. Бизнес-логика (Сервисы и Декораторы)
$productService = new ProductService($productRepo);
// Декорируем сервис кэшированием
$cachedProductProvider = new CachingProductProvider($productService, $cache);

//  Подписываем декоратор на события изменения остатков в сервисе
$productService->addObserver($cachedProductProvider);

// OrderService получает декоратор, который "умеет" в кэш
$orderService = new OrderService(
    $pdo,
    $productRepo,
    $orderRepo,
    $cachedProductProvider,
    $publisher
);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if ($method === 'GET' && $path === '/products') {
        echo json_encode(['data' => $cachedProductProvider->listProducts()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST' && $path === '/orders') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $productId = (int)($body['product_id'] ?? 0);
        $qty = (int)($body['quantity'] ?? 0);

        $result = $orderService->createOrder($productId, $qty);
        http_response_code(201);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'not found'], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    // В реальном сервисе разделили бы типы ошибок аккуратнее
    http_response_code(409);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal error'], JSON_UNESCAPED_UNICODE);
}
