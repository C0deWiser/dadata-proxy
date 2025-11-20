<?php

namespace App\Services;

use App\Models\Cleanable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class Cached
{
    protected int $age = 0;

    public function __construct(protected Builder $builder, protected array $keys)
    {
        //
    }

    /**
     * Возвращает коллекцию записей.
     *
     * @param  null|int  $maxAge  Записи не должны быть старше (в секундах).
     *
     * @return Collection<integer, Model&Cleanable>
     */
    protected function collection(?int $maxAge = null): Collection
    {
        return $this->builder->clone()
            ->when($maxAge, fn(Builder $builder) => $builder
                ->where('updated_at', '>=', now()->subSeconds($maxAge))
            )
            ->findMany($this->keys);
    }

    /**
     * Ключи, которые есть в кеше.
     *
     * @param  null|int  $maxAge  Записи не должны быть старше (в секундах).
     *
     * @return array<integer, string>
     */
    public function known(?int $maxAge = null): array
    {
        return $this->collection($maxAge)->modelKeys();
    }

    /**
     * Ключи, которых нет в кеше.
     *
     * @param  null|int  $maxAge  Записи не должны быть старше (в секундах).
     *
     * @return array<integer, string>
     */
    public function unknown(?int $maxAge = null): array
    {
        return array_diff($this->keys, $this->known($maxAge));
    }

    /**
     * Возвращает из кеша записи.
     *
     * @param  null|int  $maxAge  Записи не должны быть старше (в секундах).
     * @param  boolean  $hit  Увеличить счетчик использования кеша.
     *
     * @return array<integer, array>
     */
    public function fetch(?int $maxAge = null, bool $hit = false): array
    {
        return $this
            ->withAge($this->collection($maxAge))
            ->map(fn(Cleanable $item) => $item->hit((integer) $hit)->toApi())
            ->toArray();
    }

    /**
     * Как давно запись находится в кеше?
     *
     * @return int Секунды
     */
    public function age(): int
    {
        return $this->age;
    }

    /**
     * Вычислить, как давно запись находится в кеше?
     *
     * @param  Collection<integer, Model&Cleanable>  $collection
     *
     * @return Collection<integer, Model&Cleanable>
     */
    protected function withAge(Collection $collection): Collection
    {
        $this->age = 0;

        $oldest = $collection->min(
            fn(Cleanable $item) => $item->created_at
        );

        if ($oldest instanceof DateTimeInterface) {
            $diff = $oldest->diff(now());

            $totalDays = $diff->format('%a');
            $hours = $diff->format('%h');
            $minutes = $diff->format('%i');
            $seconds = $diff->format('%s');

            $this->age = $totalDays * 24 * 60 * 60 + $hours * 60 * 60 + $minutes * 60 + $seconds;
        }

        return $collection;
    }

    /**
     * Добавляет в кеш массив записей.
     *
     * @param  array<integer, array>  $items  Сырой ответ.
     */
    public function insertOrUpdate(array $items): void
    {
        foreach ($items as $item) {
            try {
                $model = $this->builder->clone()->updateOrCreate(
                    ['source' => $item['source']],
                    [
                        'response' => array_filter($item, fn($key) => $key !== 'source', ARRAY_FILTER_USE_KEY),
                    ]);

                $model->touch();

                Log::debug(class_basename($model::class).' was '.($model->wasRecentlyCreated ? 'created' : 'updated'), [
                    'source'     => $model->getKey(),
                    'updated_at' => $model->getAttribute('updated_at'),
                ]);
            } catch (Throwable $e) {
                Log::error($e->getMessage(), $item);
            }
        }
    }
}