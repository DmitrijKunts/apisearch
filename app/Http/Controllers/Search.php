<?php

namespace App\Http\Controllers;

use App\Actions\SearchAction;
use App\Http\XmlResponse;
use Illuminate\Http\Request;


class Search extends Controller
{
    public function __invoke(Request $request, SearchAction $action)
    {
        $res = $action->search($request);
        return $res ? XmlResponse::makeRespose($res) : $this->genNoLimits();
    }

    private function genNoLimits()
    {
        $res['response']['error'] = [
            '_attributes' => ['code' => 200],
            '_value' => 'No limits',
        ];
        return XmlResponse::makeRespose($res);
    }
}
