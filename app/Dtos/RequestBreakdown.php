<?php

namespace PicPerf\Breakdown\Dtos;

class RequestBreakdown extends AbstractData
{
    public function __construct(
        public string $unit,
        public string $url,
        public RequestBreakdownTimings $timings,
        public string $statusCode,
        public int $timeToFirstByte,
        public RequestBreakdownLocation $location,
        public ?string $effectiveUrl,
        public int $redirectCount,
        public int $redirectTime,
        public array $redirects,
        public int $responseSizeInBytes
    ) {}
}

class Header extends AbstractData
{
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}

class Redirect extends AbstractData
{
    public function __construct(
        public string $url,
        public array $headers,
        public string $statusCode,
    ) {}
}

class RequestBreakdownTimings extends AbstractData
{
    public function __construct(
        public RequestBreakdownDurations $durations,
        public RequestBreakdownRawTimingBreakdown $breakdown,
    ) {}
}

class RequestBreakdownDurations extends AbstractData
{
    public function __construct(
        public float $dnsLookup,
        public float $tcpConnection,
        public float $tlsHandshake,
        public float $serverProcessing,
        public float $contentDownload,
        public float $totalTime,
        public float $redirectTime,
    ) {}
}

class RequestBreakdownRawTimingBreakdown extends AbstractData
{
    public function __construct(
        public float $dnsLookup,
        public float $tcpConnection,
        public float $tlsHandshake,
        public float $serverProcessing,
        public float $contentDownload,
        public float $redirectTime,
    ) {}
}

class RequestBreakdownLocation extends AbstractData
{
    public function __construct(
        public string $city,
        public string $region,
        public string $country,
        public float $latitude,
        public float $longitude,
        public string $ip,
    ) {}
}
