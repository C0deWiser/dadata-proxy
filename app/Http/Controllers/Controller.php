<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

abstract class Controller
{
    /**
     * @throws ConnectionException
     */
    protected function base(Request $request, string $baseUrl, $payload = null): Response
    {
        $pendingRequest = Http::baseUrl($baseUrl);

        $headers = clone $request->headers;
        $headers->remove('host');

        $pendingRequest->withHeaders($headers->all());

        return $pendingRequest->post($request->path(), $payload ?? $request->all());
    }
}
