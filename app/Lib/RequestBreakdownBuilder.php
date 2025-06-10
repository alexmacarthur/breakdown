<?php

namespace App\Lib;

use App\Dtos\Header;
use App\Dtos\Redirect;
use App\Dtos\RequestBreakdown;
use App\Dtos\RequestBreakdownDurations;
use App\Dtos\RequestBreakdownLocation;
use App\Dtos\RequestBreakdownRawTimingBreakdown;
use App\Dtos\RequestBreakdownTimings;
use App\Traits\Formatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RequestBreakdownBuilder
{
    use Formatable;

    const UNKNOWN_LOCATION = [
        'city' => 'Unknown',
        'region' => 'Unknown',
        'country' => 'Unknown',
        'latitude' => 0.0,
        'longitude' => 0.0,
        'ip' => 'Unknown',
    ];

    public function build(
        string $url,
        \CurlHandle $ch,
        Collection $redirects
    ): RequestBreakdown {
        $nameLookup = curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME);
        $connect = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
        $appConnect = curl_getinfo($ch, CURLINFO_APPCONNECT_TIME);
        $preTransfer = curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME);
        $startTransfer = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME); // TTFB
        $total = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $wasTls = $appConnect > 0;
        $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        $redirectTime = curl_getinfo($ch, CURLINFO_REDIRECT_TIME);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        $redirectTimeMs = $this->secondsToMilliseconds($redirectTime);

        $durations = [
            'redirectTime' => $this->secondsToMilliseconds($redirectTime),
            'dnsLookup' => $this->secondsToMilliseconds($nameLookup),
            'tcpConnection' => $this->secondsToMilliseconds($connect - $nameLookup),
            'tlsHandshake' => $this->secondsToMilliseconds($wasTls ? $appConnect - $connect : 0),
            'serverProcessing' => $this->secondsToMilliseconds($startTransfer - $preTransfer),
            'contentDownload' => $this->secondsToMilliseconds($total - $startTransfer),
            'finalRequestTime' => $this->secondsToMilliseconds($total - $redirectTime),
            'totalTime' => $this->secondsToMilliseconds($total),
        ];

        $breakdown = $this->buildBreakdown($durations);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return RequestBreakdown::from([
            'url' => $url,
            'unit' => 'ms',
            'statusCode' => $this->statusToText($statusCode),
            'timeToFirstByte' => $this->secondsToMilliseconds($startTransfer),
            'timings' => RequestBreakdownTimings::from([
                'durations' => RequestBreakdownDurations::from($durations),
                'breakdown' => RequestBreakdownRawTimingBreakdown::from($breakdown),
            ]),
            'redirectCount' => $redirectCount,
            'redirectTime' => $this->secondsToMilliseconds($redirectTime),
            'effectiveUrl' => $effectiveUrl,
            'location' => RequestBreakdownLocation::from($this->getTestLocation()),
            'redirects' => $redirects->map(function ($redirect) {
                $redirect['headers'] = collect($redirect['headers'])
                    ->map(fn ($header) => Header::from($header))
                    ->filter()
                    ->all();

                return Redirect::from($redirect);
            })->all(),
            'responseSizeInBytes' => curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
        ]);
    }

    private function buildBreakdown(array $timings): array
    {
        $breakdown = [];
        $timingKeys = ['redirectTime', 'dnsLookup', 'tcpConnection', 'tlsHandshake', 'serverProcessing', 'contentDownload'];

        $sum = 0;
        foreach ($timingKeys as $key) {
            $sum += $timings[$key] ?? 0;
        }

        $sum = $sum ?: 1;
        foreach ($timingKeys as $key) {
            $breakdown[$key] = round(($timings[$key] ?? 0) / $sum, 5);
        }

        return $breakdown;
    }

    public function getTestLocation(): ?array
    {
        try {
            $response = Http::get('http://ip-api.com/json/');

            if ($response->successful() && $response->json()['status'] === 'success') {
                return [
                    'city' => $response->json('city', 'Unknown'),
                    'region' => $response->json('regionName', 'Unknown'),
                    'country' => $response->json('country', 'Unknown'),
                    'latitude' => (float) $response->json('lat', 0.0),
                    'longitude' => (float) $response->json('lon', 0.0),
                    'ip' => $response->json('query', 'Unknown'),
                ];
            }

            return self::UNKNOWN_LOCATION;
        } catch (\Exception $e) {
            dd($e);
            Log::error('Geolocation request failed', [
                'message' => $e->getMessage(),
                'url' => 'http://ip-api.com/json/',
            ]);

            return self::UNKNOWN_LOCATION;
        }
    }
}
