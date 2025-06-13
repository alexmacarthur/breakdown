<?php

namespace PicPerf\Breakdown\Services;

use Illuminate\Support\Str;
use PicPerf\Breakdown\Dtos\RequestBreakdown;
use PicPerf\Breakdown\Lib\RequestBreakdownBuilder;
use PicPerf\Breakdown\Traits\Formatable;

class RequestBreakdownService
{
    use Formatable;

    private RequestBreakdownBuilder $builder;

    public function __construct()
    {
        $this->builder = new RequestBreakdownBuilder();
    }

    public function analyze(string $url): RequestBreakdown
    {
        $ch = curl_init();
        $redirects = [];
        $currentHeaders = '';
        $currentUrl = $url;

        $headerCallback = function ($ch, $header) use (&$redirects, &$currentHeaders, &$currentUrl) {
            $len = strlen($header);

            if (preg_match('/^HTTP\//i', $header)) {
                if ($currentHeaders) {
                    $redirects[] = [
                        'url' => $currentUrl,
                        'headers' => collect($this->breakHeader($currentHeaders))
                            ->map(fn ($value, $key) => ['name' => $key, 'value' => $value])
                            ->values()
                            ->all(),
                        'statusCode' => $this->statusToText((int) explode(' ', $currentHeaders)[1]),
                    ];
                }

                $currentHeaders = '';
            }

            $currentHeaders .= $header;

            return $len;
        };

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache, no-store, must-revalidate',
                'Pragma: no-cache',
                'Expires: 0',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_HEADERFUNCTION => $headerCallback,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);

            throw new \Exception('cURL Error: '.curl_error($ch));
        }

        $result = $this->builder->build(
            url: $url,
            ch: $ch,
            redirects: collect($redirects
            ));

        curl_close($ch);

        return $result;
    }

    private function breakHeader(string $headers)
    {
        return collect(explode("\r\n", $headers))
            ->map(function ($h, $index) {
                if (empty($h)) {
                    return null;
                }

                if ($index === 0 && Str::startsWith($h, 'HTTP/')) {
                    return null;
                }

                $key = Str::of($h)->before(':')->trim()->toString();
                $value = Str::of($h)->after(':')->trim()->toString();

                return [$key => $value];
            })
            ->filter()
            ->mapWithKeys(fn ($item) => $item)
            ->toArray();
    }
}
