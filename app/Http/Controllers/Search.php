<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\ArrayToXml\ArrayToXml;

class Search extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $accs = Str::of(Storage::get('accs.txt'))
            ->explode(PHP_EOL)
            ->filter(fn ($i) => trim($i) != '')
            ->map(fn ($i) => Str::of($i)->split('~\s+~')->all())
            ->shuffle()
            ->all();

        foreach ($accs as $acc) {
            if (!Cache::has($acc[0])) {
                $params = collect([
                    'cx' => $acc[0],
                    'key' => $acc[1],
                ])->merge($request->all())
                    ->all();
                if (isset($params['query'])) $params['q'] = $params['query'];
                try {
                    $response = Http::get('https://www.googleapis.com/customsearch/v1', $params);
                } catch (\Exception $e) {
                    Cache::put($acc[0], true, 60 * 5); //pause 5 min
                    continue;
                }
                if (!$response->successful()) {
                    Cache::put($acc[0], true, 60 * 5); //pause 5 min
                    continue;
                }

                $grouping = [];
                $json = json_decode($response->body());
                foreach ($json->items as $k => $item) {
                    $e = [
                        '_attributes' => ['id' => (int)($k + 1)],
                        'doccount' => 1,
                        'doc' => [
                            'url' => $item->link,
                            'breadcrumbs' => $item->link,
                            'title' => $item->title,
                            'contenttype' => 'organic',
                            'passages' => ['passage' => $item->snippet],
                        ]
                    ];
                    $grouping['group'][] = $e;
                }
                $res['response']['results']['grouping'] = $grouping;

                $res['response']['found']['_attributes'] = ['priority' => 'all'];
                $res['response']['found']['_value'] = $json->searchInformation->totalResults;

                $res['response']['results']['grouping']['page']['_value'] = '0';
                $res['response']['results']['grouping']['page']['_attributes'] = ['first' => '1', 'last' => 9];

                return $this->genResponse($res);
            }
        }
        return $this->genNoLimits();
    }


    private function genNoLimits()
    {
        $res['response']['error'] = [
            '_attributes' => ['code' => 200],
            '_value' => 'No limits',
        ];
        return $this->genResponse($res);
    }

    private function genResponse($array)
    {
        $array['response']['_attributes'] = ['date' => now()->format('Ymd\THis')];
        return response(ArrayToXml::convert($array, [
            'rootElementName' => 'yandexsearch',
            '_attributes' => ['version' => '1.0']
        ]))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
