<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Clean extends Model implements Cleanable
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
