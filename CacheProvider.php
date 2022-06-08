<?php

use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheProvider - абстрактный класс, реализующий интерфейс CacheInterface
 */
abstract class CacheProvider implements CacheInterface
{
    use AssertTrait;

    public function get($key, $default = null): ?string
    {
        $this->assertArgumentString($key);
        $data = $this->doGet($key);
        if (!empty($data)) {
            if ($this->isExpired($data['ttl'], $data['timestamp'])) {
                $this->delete($key);
                $data['value'] = $default;
            }
            $default = $data['value'];
        }
        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->assertArgumentString($key);
        $this->assertValidTypeOfTtl($ttl);
        $timestamp = time();
        if (is_null($ttl)) {
            $ttl = 0;
        } elseif ($ttl instanceof DateInterval) {
            $datetimeObj = new DateTime();
            $datetimeObj->add($ttl);
            $ttl = $datetimeObj->getTimestamp() - $timestamp;
        }
        return $this->doSet($key, $value, $ttl, $timestamp);
    }

    public function delete($key): bool
    {
        $this->assertArgumentString($key);
        return $this->doDelete($key);
    }

    public function clear(): bool
    {
        return $this->doClear();
    }

    public function has($key): bool
    {
        $this->assertArgumentString($key);
        if ($this->doHas($key)) {
            return true;
        }
        return false;
    }

    public function getMultiple($keys, $default = null): array
    {
        $this->assertArgumentIterable($keys);
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }


    public function setMultiple($values, $ttl = null): bool
    {
        $this->assertArgumentIterable($values);
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $this->assertArgumentIterable($keys);
        foreach ($keys as $key) {
            if (!$this->doDelete($key)) {
                return false;

            }
        }
        return true;
    }

    /**
     * проверка истекло ли время жизни кэша
     * @param int $ttl - время жизни
     * @param int $timestamp - временная метка
     * @return bool
     */
    protected function isExpired(int $ttl, int $timestamp): bool
    {
        $now = time();
        if (empty($ttl)) {
            return false;
        } elseif ($now - $timestamp < $ttl) {
            return false;
        }
        return true;
    }

    /**
     * Получить кэш используя реализацию
     * @param string $key - ключ
     * @return array  - значение кэша с ключом $key. Возвращается ассоциативный массив:
     * ['value' => $value, 'ttl' => $ttl,'timestamp' => $timestamp]
     */
    abstract protected function doGet(string $key): array;

    /**
     * Сохранить в кэш значение $value по ключу $key со временем хранения
     *
     * @param string $key - ключ
     * @param mixed $value - значение
     * @param int $ttl
     * @param int $timestamp
     * @return bool
     */
    abstract protected function doSet(string $key, $value, int $ttl, int $timestamp): bool;

    /**
     * Удалить кэш по ключу
     * @param string $key
     * @return bool
     */
    abstract protected function doDelete(string $key): bool;

    /**
     * Очистить
     * @return bool
     */
    abstract protected function doClear(): bool;

    /**
     * Проверка наличия кэша по ключу
     * @param string $key
     * @return bool
     */
    abstract protected function doHas(string $key): bool;
}
