<?php

namespace App\Http;
use Spatie\ArrayToXml\ArrayToXml;

class XmlResponse
{
    public static function makeRespose($array)
    {
        $array['response']['_attributes'] = ['date' => now()->format('Ymd\THis')];
        return response(ArrayToXml::convert($array, [
            'rootElementName' => 'yandexsearch',
            '_attributes' => ['version' => '1.0']
        ]))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
