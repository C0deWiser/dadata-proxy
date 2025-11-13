<?php

namespace App\Models;

interface Apiable
{
    public function toApi(): array;
}