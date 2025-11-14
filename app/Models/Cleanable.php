<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * @property string $source
 * @property array $response
 * @property integer $hits
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 */
interface Cleanable
{
    public function toApi(): array;

    public function hit(int $count): static;
}