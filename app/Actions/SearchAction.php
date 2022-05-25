<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class SearchAction
{
    private const API_URL = 'https://www.googleapis.com/customsearch/v1';

    public function search(Request $request)
    {
        $accs = $this->getAccs();

        foreach ($accs as $acc) {
            if (!Cache::has($acc[0])) {
                $params = collect([
                    'cx' => $acc[0],
                    'key' => $acc[1],
                ])->merge($request->all())
                    ->all();
                if (isset($params['query'])) $params['q'] = $params['query'];
                try {
                    $response = Http::get(self::API_URL, $params);
                } catch (\Exception $e) {
                    Cache::put($acc[0], true, 60 * 5); //pause 5 min
                    continue;
                }
                if (!$response->successful()) {
                    Cache::put($acc[0], true, 60 * 5); //pause 5 min
                    continue;
                }

                return $this->makeGroup($response);
            }
        }
        return null;
    }


    private function getAccs()
    {
        return Str::of(Storage::get('accs.txt'))
            ->explode(PHP_EOL)
            ->filter(fn ($i) => trim($i) != '')
            ->map(fn ($i) => Str::of($i)->split('~\s+~')->all())
            ->shuffle()
            ->all();
    }

    private function makeGroup($response)
    {
        $grouping = [];
        $json = json_decode($response->body());
        foreach ($json->items ?? [] as $k => $item) {
            $e = [
                '_attributes' => ['id' => (int)($k + 1)],
                'doccount' => 1,
                'doc' => [
                    'url' => $item->link,
                    'breadcrumbs' => $item->link,
                    'title' => $item->title,
                    'contenttype' => 'organic',
                    'passages' => ['passage' => $item->snippet ?? ''],
                ]
            ];
            $grouping['group'][] = $e;
        }
        return [
            'response' => [
                'found' => [
                    '_attributes' => ['priority' => 'all'],
                    '_value' => $json->searchInformation->totalResults
                ],
                'results' => [
                    'grouping' => [
                        array_merge(
                            ['page' => [
                                '_value' => '0',
                                '_attributes' => ['first' => '1', 'last' => 9]
                            ]],
                            $grouping
                        )
                    ],
                ],
            ],
        ];
    }
}
