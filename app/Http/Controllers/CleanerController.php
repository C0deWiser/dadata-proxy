<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Apiable;
use App\Models\Email;
use App\Models\Name;
use App\Models\Passport;
use App\Models\Phone;
use App\Models\Vehicle;
use App\Services\CacheControl;
use App\Services\Cached;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanerController extends BaseController
{
    protected function clean(Request $request, Builder $builder)
    {
        $cache = new Cached($builder, $request->all());
        $cc = new CacheControl($request->header('Cache-Control'));

        Log::debug($request->path(), $request->all());
        if ($cc->toArray()) {
            Log::debug('Cache-Control', $cc->toArray());
        }

        if ($cc->onlyIfCached()) {
            Log::debug('Return only cached records');
        } else {

            if ($cc->noCache()) {
                Log::debug('Dont use cache, must revalidate');
            } elseif ($cc->maxAgeWithFresh()) {
                Log::debug("Records older than {$cc->maxAgeWithFresh()} second(s) are stale");
            }

            $unknown = $cc->noCache()
                // Запросим у dadata все записи
                ? $request->all()
                // Запросим у dadata только те записи, которых нет в кеше.
                // Всё что старше max-age мы, типа, «не знаем».
                : $cache->unknown($cc->maxAgeWithFresh());

            if ($unknown) {

                Log::debug('Unknown records', $unknown);

                try {
                    $response = $this->base(
                        $request,
                        'https://cleaner.dadata.ru',
                        $unknown
                    )->throw();

                    Log::info($request->path(), $unknown);

                    if ($cc->noStore()) {
                        Log::debug('No-store, just respond');

                        // В кеш не сохраняем, отдаем и всё
                        return $response->json();
                    }

                    $cache->insertOrUpdate($response->json());

                } catch (ConnectionException|RequestException $e) {

                    if ($e instanceof RequestException) {
                        Log::error($e->getMessage(), (array) $e->response->json());
                    } else {
                        Log::error($e->getMessage());
                    }

                    // Обычно ошибку мы тоже проксируем.
                    // Но если мы пытались освежить stale записи, то ошибку возвращать не нужно.
                    if ($cc->maxStale()) {
                        Log::debug('Allowed to use stale cache, dont respond with an error');
                    } else {
                        return $e instanceof RequestException
                            ? $e->response
                            : response()->json([], 500);
                    }
                }
            }
        }

        if ($cc->maxAgeWithStale()) {
            Log::debug("Return records younger than {$cc->maxAgeWithStale()} second(s)");
        }

        return $cache->fetch($cc->maxAgeWithStale());
    }

    public function name(Request $request)
    {
        return $this->clean($request, Name::query());
    }

    public function phone(Request $request)
    {
        return $this->clean($request, Phone::query());
    }

    public function email(Request $request)
    {
        return $this->clean($request, Email::query());
    }

    public function address(Request $request)
    {
        return $this->clean($request, Address::query());
    }

    public function passport(Request $request)
    {
        return $this->clean($request, Passport::query());
    }

    public function vehicle(Request $request)
    {
        return $this->clean($request, Vehicle::query());
    }
}
