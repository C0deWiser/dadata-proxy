<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $source
 * @property array $response
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 */
class Name extends Model
{
    protected $primaryKey = 'source';
    protected $keyType = 'string';

    protected $fillable = ['source', 'response'];

    protected function casts(): array
    {
        return [
            'response' => 'json:unicode',
        ];
    }

    public function toApi(): array
    {
        return ['source' => $this->source] + $this->response;
    }
}
