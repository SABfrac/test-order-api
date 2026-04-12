## Запуск

1.Клонируем репозиторий

2.Стартуем через Docker

docker compose up -d

Установить зависимости (если не установились автоматически)

docker-compose exec app composer install

docker-compose stop worker - остановка воркера


## Таблицы

CREATE TABLE products (
                          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(255) NOT NULL,
                          price DECIMAL(10,2) NOT NULL,
                          stock INT UNSIGNED NOT NULL,
                          INDEX(stock)
) ENGINE=InnoDB;

CREATE TABLE orders (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        product_id BIGINT UNSIGNED NOT NULL,
                        quantity INT UNSIGNED NOT NULL,
                        total_price DECIMAL(10,2) NOT NULL,
                        status VARCHAR(32) NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;



INSERT INTO products(name, price, stock) VALUES
                                             ('Pants', 3000.90, 10),
                                             ('Tshirt', 1500, 25),
                                             ('Hoodie', 3500.25, 5);

## для postman 
(POST)
http://localhost:8000/orders
в body 
{
  "product_id": 2,
  "quantity": 5
}

(GET)
http://localhost:8000/products

 Описание

1. Слой инфраструктуры (Infrastructure)
 низкоуровневые классы 
Connection: Создает канал к RabbitMQ.
RedisCache: Обертка над Redis. 
PDO: Прямое соединение с БД.
2. Слой доступа к данным (Repository)
Этот слой взаимодействия с БД.
ProductRepository:
getForUpdate(id) — блокирует строку в БД.
decrementStock(id, qty) — выполняет атомарный UPDATE.
OrderRepository:
create(...) — делает INSERT в таблицу заказов.
3. Слой обмена сообщениями (Messaging)
Publisher: Принимает массив данных и отправляет его в конкретный exchange. Благодаря DI, названия очередей  получает при создании.
Consumer: Слушает очередь. 
4. Слой бизнес-логики (Service)
 Здесь принимаются решения и координируются другие слои.
ProductService:
Отвечает за состояние продуктов.
Связка: Если сток изменился в репозитории Redis очистить кэш.
OrderService:
это слой бизнес-логики, который координирует работу репозиториев и других сервисов. Асинхронное взаимодействие через RabbitMQ реализует событийную модель, где Consumer выступает в роли удаленного наблюдателя за событием создания заказа».

Алгоритм: Открывает транзакцию -> репозиторий лочит строку -> ProductService уменьшить stock ->  OrderRepository создать запись -> Коммитит -> Publisher отправляет уведомление.

Реализация кеширования (паттерны Decorator , Observer)
полностью отделена логика хранения данных в Redis от бизнес-логики управления заказами

1.Интерфейс ProductProvider: Определяет контракт для получения продуктов и списания остатков. Его использует  OrderService.
2.Декоратор CachingProductProvider: "Обертка" над основным сервисом. Он перехватывает запросы на чтение и проверяет наличие данных в Redis.
3.Механизм Observer: ProductService является субъектом, а декоратор — наблюдателем. Как только остатки товара успешно изменяются в БД, сервис уведомляет декоратор, и тот мгновенно удаляет устаревший кэш.



