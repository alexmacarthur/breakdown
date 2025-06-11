<?php

namespace PicPerf\Breakdown\Lib;

use PicPerf\Breakdown\Dtos\RequestBreakdown;
use PicPerf\Breakdown\Traits\Formatable;

use function Termwind\render;
use function Termwind\terminal;

class BreakdownViewBuilder
{
    use Formatable;

    public function buildView(RequestBreakdown $breakdownData): void
    {
        $timingLabels = [
            'dnsLookup' => ['DNS Lookup     ', '[DNS]', 'text-blue'],
            'tcpConnection' => ['TCP Connection ', '[TCP]', 'text-green'],
            'tlsHandshake' => ['TLS Handshake  ', '[TLS]', 'text-yellow'],
            'serverProcessing' => ['Server Time    ', '[SRV]', 'text-red'],
            'contentDownload' => ['Content Download', '[DWN]',  'text-cyan'],
        ];

        if (isset($breakdown->timings->durations->redirectTime)) {
            $timingLabels['redirectTime'] = ['Redirect Time', '↪️', 'text-magenta'];
        }

        $html = view('output', [
            'breakdown' => $this->enforceCharacterMaximumOnAllRedirectHeaderValues($breakdownData),
            'timingLabels' => $timingLabels,
            'terminalWidth' => terminal()->width(),
        ])->render();

        render($this->removeLineBreaks($html));
    }

    private function enforceCharacterMaximumOnAllRedirectHeaderValues(RequestBreakdown $breakdown): RequestBreakdown
    {
        $maxWidth = terminal()->width() - 25;
        foreach ($breakdown->redirects as $redirect) {
            foreach ($redirect->headers as $header) {
                if (strlen($header->value) > 100) {
                    $header->value = substr($header->value, 0, $maxWidth).'...';
                }
            }
        }

        return $breakdown;
    }
}
