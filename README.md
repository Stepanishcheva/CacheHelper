# CacheHelper
Реализация PSR-16 c хранением кэша в базе данных (PostgreSQL)

### Установка с помощью composer 
> `composer require anst/cache-saver`

### Использование
$cache = new CacheHelper($config);

Для подключения необходимо передать следущие параметры. **User, pass, dbname - обязательные параметры**.\
$config = [ 'host' => 'localhost', 'port' => 5432, **'user' => , 'pass' => , 'dbname' =>,** 'table' => 'cache' ];

#### Доступные методы: 

1. Получение кэша по ключу `$cache->get($key, $default = null)`
2. Сохранение кэша с возможностью указания времени жизни `$cache->set($key, $value, $ttl = null)`
3. Удаление кэша по ключу `$cache->delete($key)`
4. Очистка кэша `$cache->clear()`
5. Проверка наличия кэша по ключу `$cache->has($key)`
6. Получение кэша по набору ключей `$cache->getMultiple($keys, $default = null)`
7. Сохранение кэша по набору ключей `$cache->setMultiple($values, $ttl = null)`
8. Удаление кэша по набору ключей `$cache->deleteMultiple($keys)`
9. **Дополнительный** метод для удаления мусора из бд(если время жизни кэша прошло, то это мусор) `$cache->collectGarbage()`
