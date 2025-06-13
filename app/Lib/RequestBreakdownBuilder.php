<?php

namespace PicPerf\Breakdown\Lib;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PicPerf\Breakdown\Dtos\Header;
use PicPerf\Breakdown\Dtos\Redirect;
use PicPerf\Breakdown\Dtos\RequestBreakdown;
use PicPerf\Breakdown\Dtos\RequestBreakdownDurations;
use PicPerf\Breakdown\Dtos\RequestBreakdownLocation;
use PicPerf\Breakdown\Dtos\RequestBreakdownRawTimingBreakdown;
use PicPerf\Breakdown\Dtos\RequestBreakdownTimings;
use PicPerf\Breakdown\Traits\Formatable;

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
            $client = new Client();
            $response = $client->get('http://ip-api.com/json/');
            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['regionName'] ?? 'Unknown',
                    'country' => $data['country'] ?? 'Unknown',
                    'latitude' => (float) ($data['lat'] ?? 0.0),
                    'longitude' => (float) ($data['lon'] ?? 0.0),
                    'ip' => $data['query'] ?? 'Unknown',
                ];
            }

            return self::UNKNOWN_LOCATION;
        } catch (\Exception|GuzzleException $e) {
            Log::error('Geolocation request failed', [
                'message' => $e->getMessage(),
                'url' => 'http://ip-api.com/json/',
            ]);

            return self::UNKNOWN_LOCATION;
        }
    }
}
