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

        // $grouping = ['1' => '1', '2' => '2'];
        // $res['yandexsearch']['response']['results']['grouping'] = $grouping;
        // return response(ArrayToXml::convert($res))->header('Content-Type', 'application/xml; charset=UTF-8');

        foreach ($accs as $acc) {
            $count = (int)Cache::get($acc[0]);
            if ($count < 100) {
                $params = collect([
                    'cx' => $acc[0],
                    'key' => $acc[1],
                ])->merge($request->all())
                    ->all();
                if (isset($params['query'])) $params['q'] = $params['query'];
                Cache::put($acc[0], $count + 1, 60 * 60 * 24);
                try {
                    $response = Http::get('https://www.googleapis.com/customsearch/v1', $params);
                } catch (\Exception $e) {
                    abort(429);
                }
                if (!$response->successful()) abort(429);

                // dd($response->body());
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
                $res['response']['_attributes'] = ['date' => '20220502T171254'];

                $res['response']['found']['_attributes'] = ['priority' => 'all'];
                $res['response']['found']['_value'] = $json->searchInformation->totalResults;

                $res['response']['results']['grouping']['page']['_value'] = '0';
                $res['response']['results']['grouping']['page']['_attributes'] = ['first' => '1', 'last' => 9];

                return response(ArrayToXml::convert($res, [
                    'rootElementName' => 'yandexsearch',
                    '_attributes' => ['version' => '1.0']
                ]))
                    ->header('Content-Type', 'application/xml; charset=UTF-8');
            }
        }
        abort(429);
    }
}
