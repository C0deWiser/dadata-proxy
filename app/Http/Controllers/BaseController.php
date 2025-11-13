<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    public function cleaner(Request $request)
    {
        return $this->base($request, 'https://cleaner.dadata.ru');
    }

    /**
     * @throws ConnectionException
     */
    protected function base(Request $request, string $baseUrl, $payload = null, bool $async = false): \Illuminate\Http\Client\Response
    {
        $pendingRequest = Http::baseUrl($baseUrl)->async($async);

        $headers = $request->headers->all();
        $headers['host'] = str($baseUrl)->after('://')->toString();

        $pendingRequest->withHeaders($headers);

        return $pendingRequest->post($request->path(), $payload ?? $request->all());
    }
}
