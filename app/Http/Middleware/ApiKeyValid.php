<?php

namespace App\Http\Middleware;

use App\Http\XmlResponse;
use Closure;
use Illuminate\Http\Request;

class ApiKeyValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->input('api_key') != config('app.api_key')) {
            $res['response']['error'] = [
                '_attributes' => ['code' => 403],
                '_value' => 'Forbidden',
            ];
            return XmlResponse::makeRespose($res);
        }
        return $next($request);
    }
}
