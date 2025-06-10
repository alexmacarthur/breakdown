<?php

namespace App\Commands;

use App\Lib\BreakdownViewBuilder;
use App\Services\RequestBreakdownService;
use LaravelZero\Framework\Commands\Command;

use function Termwind\render;

class BreakDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:breakdown {url : The URL to breakdown}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(RequestBreakdownService $service, BreakdownViewBuilder $viewBuilder)
    {
        $url = $this->argument('url');

        render("<span class='ml-2 mt-1 text-sky-500'>Hold up! Analyzing <span class='font-bold'>$url</span>");

        $viewBuilder->buildView(
            $service->analyze($url)
        );
    }
}
