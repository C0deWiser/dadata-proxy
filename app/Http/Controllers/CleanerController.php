<?php

namespace App\Http\Controllers;

use App\Models\Apiable;
use App\Models\Email;
use App\Models\Name;
use App\Models\Phone;
use App\Services\CacheControl;
use App\Services\Cached;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanerController extends BaseController
{
    protected function clean(Request $request, Builder $builder, string $service)
    {
        $cache = new Cached($builder, $request->all());
        $cc = new CacheControl($request->header('Cache-Control'));

        if (! $cc->onlyIfCached()) {

            $unknown = $cc->noCache()
                // Запросим у dadata все записи
                ? $request->all()
                // Запросим у dadata только те записи, которых нет в кеше.
                // Всё что старше max-age мы, типа, «не знаем».
                : $cache->unknown($cc->maxAge());

            if ($unknown) {
                try {
                    $response = $this->base($request, 'https://cleaner.dadata.ru', $unknown);

                    Log::debug("clean/$service", $unknown);

                    if ($cc->noStore()) {
                        // В кеш не сохраняем, отдаем и всё
                        return $response->json();
                    }

                    $cache->insertOrUpdate($response->json());

                } catch (Throwable) {
                    //
                }
            }
        }

        return $cache->fetch($cc->maxAgeWithStale());
    }

    public function name(Request $request)
    {
        return $this->clean($request, Name::query(), 'name');
    }

    public function phone(Request $request)
    {
        return $this->clean($request, Phone::query(), 'phone');
    }

    public function email(Request $request)
    {
        return $this->clean($request, Email::query(), 'email');
    }
}
