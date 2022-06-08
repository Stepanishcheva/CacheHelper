<?php

trait AssertTrait
{
    /**
     * Проверка на пустоту конфигурационных полей
     * @param array $settings - конфигурация
     * @throws CacheArgumentException
     */
    protected function assertSettingFields(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (empty($value)) {
                throw new CacheArgumentException(
                    sprintf(
                        'Параметр "%s" в конфигурации не может буть пустым',
                        $key
                    )
                );
            }
        }
    }
    /**
     * Проверка типа переданных строковых данных
     * @param $value
     */
    protected function assertArgumentString($value): void
    {
        if (!is_string($value)) {
            throw new CacheArgumentException(
                sprintf(
                    'The type of value must be string, but "%s" provided.',
                    gettype($value)
                )
            );
        }
    }

    /**
     * Проверка на перечисляемость(массив, лист и др.)
     * @param $value
     */
    protected function assertArgumentIterable($value): void
    {
        if (!is_iterable($value)) {
            throw new CacheArgumentException(
                sprintf(
                    'Тип данных должен быть iterable, а получен "%s".',
                    gettype($value)
                )
            );
        }
    }

    /**
     * Проверка на корректность типа TTL - time-to-live - время жизни кэша
     * @throws CacheArgumentException
     */
    protected function assertValidTypeOfTtl($ttl): void
    {
        if (
            !is_null($ttl) &&
            !is_integer($ttl) &&
            !($ttl instanceof DateInterval)
        ) {
            throw new CacheArgumentException(
                sprintf(
                    'TTL(time-to-live) может принимать значения только типов int, null или DateInterval, а получен экземпляр "%s". ',
                    gettype($ttl)
                )
            );
        }
    }


}