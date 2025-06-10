<?php

namespace App\Traits;

use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

trait Formatable
{
    public function formatBytes($bytes)
    {
        $marker = 1024;
        $decimal = 2;
        $kiloBytes = $marker;
        $megaBytes = $marker * $marker;
        $gigaBytes = $marker * $marker * $marker;

        if ($bytes < $kiloBytes) {
            return $bytes.' Bytes';
        }

        if ($bytes < $megaBytes) {
            return number_format($bytes / $kiloBytes, $decimal).' KB';
        }

        if ($bytes < $gigaBytes) {
            return number_format($bytes / $megaBytes, $decimal).' MB';
        }

        return number_format($bytes / $gigaBytes, $decimal).' GB';
    }

    public function toKb(int|float $value): string
    {
        $num = round($value / 1000);

        return Number::format($num).'kb';
    }

    public function secondsToMilliseconds(float $seconds): float
    {
        return round($seconds * 1000, 5);
    }

    public function statusToText(int $statusCode): string
    {
        return "$statusCode ".Response::$statusTexts[$statusCode];
    }

    public function removeLineBreaks(string $text): string
    {
        return str_replace(["\r", "\n"], '', $text);
    }
}
