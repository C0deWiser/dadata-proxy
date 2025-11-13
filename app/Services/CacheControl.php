<?php

namespace App\Services;

class CacheControl
{
    protected array $options = [];

    public function __construct(null|string|array $header)
    {
        if (is_string($header)) {
            $header = explode(',', $header);
            $header = array_map('trim', $header);
        }

        if (is_array($header)) {
            $this->options = $header;
        }
    }

    /**
     * @example max-age=10 -> 10
     * @example max-stale -> true
     * @example max-stale=10 -> 10
     * @example no-cache -> true
     */
    protected function get(string $option): null|true|int
    {
        foreach ($this->options as $item) {
            if (str_starts_with($item, $option)) {
                if (str_contains($item, '=')) {
                    list($key, $value) = explode('=', $item);
                    return $value;
                } else {
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Задаёт максимальное время в течение которого ресурс будет считаться актуальным.
     */
    public function maxAge(): ?int
    {
        return $this->get('max-age');
    }

    /**
     * Указывает, что клиент хочет получить ответ, для которого было превышено
     * время устаревания. Дополнительно может быть указано значение в секундах,
     * указывающее, что ответ не должен быть просрочен более чем на указанное
     * значение.
     */
    public function maxStale(): null|int|true
    {
        return $this->get('max-stale');
    }

    /**
     * Указывает, что клиент хочет получить ответ,
     * который будет актуален как минимум указанное количество секунд.
     */
    public function minFresh(): ?int
    {
        return $this->get('min-fresh');
    }

    /**
     * Указывает на необходимость отправить запрос на сервер для валидации
     * ресурса перед использованием закешированных данных.
     */
    public function noCache(): bool
    {
        return (boolean) $this->get('no-cache');
    }

    /**
     * Кеш не должен хранить никакую информацию о запросе и ответе.
     */
    public function noStore(): bool
    {
        return (boolean) $this->get('no-store');
    }

    /**
     * Указывает на необходимость использования только закешированных данных.
     * Запрос на сервер не должен посылаться.
     */
    public function onlyIfCached(): bool
    {
        return (boolean) $this->get('only-if-cached');
    }

    /**
     * Максимальное время актуальности с учетом min-fresh.
     */
    public function maxAgeWithFresh(): ?int
    {
        if ($this->maxAge()) {
            return $this->maxAge() - (int) $this->minFresh();
        }

        return null;
    }

    /**
     * Максимальное время актуальности с учетом max-stale.
     */
    public function maxAgeWithStale(): ?int
    {
        if ($this->maxAge()) {
            if ($this->maxStale() === true) {
                // Разрешено использовать протухшее без ограничений.
                return null;
            }

            // Использовать протухшие на определенное время
            return $this->maxAge() + (int) $this->maxStale();
        }

        return null;
    }

    public function toArray(): array
    {
        return array_filter([
            'max-age'           => $this->maxAge(),
            'max-stale'         => $this->maxStale(),
            'min-fresh'         => $this->minFresh(),
            'no-cache'          => $this->noCache(),
            'no-store'          => $this->noStore(),
            'only-if-cached'    => $this->onlyIfCached(),
            //'max-age-and-fresh' => $this->maxAgeWithFresh(),
            //'max-age-and-stale' => $this->maxAgeWithStale(),
        ]);
    }
}