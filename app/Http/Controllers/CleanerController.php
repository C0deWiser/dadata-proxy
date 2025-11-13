<?php

namespace App\Http\Controllers;

use App\Models\Name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CleanerController extends BaseController
{
    public function names(Request $request)
    {
        $names = $request->all();

        $known = Name::query()
            ->findMany($names)
            ->modelKeys();

        $unknown = array_diff($names, $known);

        if ($unknown) {
            $response = $this->base($request, 'https://cleaner.dadata.ru', $unknown);

            Log::debug('clean/name', $unknown);

            foreach ($response->json() as $item) {
                Name::query()->create([
                    'source'   => $item['source'],
                    'response' => array_filter($item, fn($key) => $key !== 'source', ARRAY_FILTER_USE_KEY),
                ]);
            }
        }

        return Name::query()
            ->findMany($names)
            ->map(fn(Name $name) => $name->toApi())
            ->toArray();
    }
}
