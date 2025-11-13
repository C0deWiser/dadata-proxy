<?php

namespace App\Services;

use App\Models\Apiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class Cached
{
    public function __construct(
        protected Builder $builder,
        protected array $keys
    ) {
        //
    }

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
     */
    public function known(?int $maxAge = null): array
    {
        return $this->collection($maxAge)->modelKeys();
    }

    /**
     * Ключи, которых нет в кеше.
     */
    public function unknown(?int $maxAge = null): array
    {
        return array_diff($this->keys, $this->known($maxAge));
    }

    /**
     * Возвращает из кеша записи.
     *
     * @param  null|int  $maxAge  Записи не должны быть старше...
     *
     * @return array
     */
    public function fetch(?int $maxAge = null): array
    {
        return $this->collection($maxAge)
            ->map(fn(Apiable $item) => $item->toApi())
            ->toArray();
    }

    /**
     * Добавляет в кеш массив записей.
     */
    public function insertOrUpdate(array $items): void
    {
        foreach ($items as $item) {
            $model = $this->builder->clone()->updateOrCreate(
                ['source' => $item['source']],
                [
                    'response' => array_filter($item, fn($key) => $key !== 'source', ARRAY_FILTER_USE_KEY),
                ]);

            $model->touch();

            Log::debug($model::class.' was '.($model->wasRecentlyCreated ? 'created' : 'updated'), [
                'source'     => $model->getKey(),
                'updated_at' => $model->getAttribute('updated_at'),
            ]);
        }
    }
}