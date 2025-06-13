<?php

namespace PicPerf\Breakdown\Commands;

use PicPerf\Breakdown\Lib\BreakdownViewBuilder;
use Symfony\Component\Console\Command\Command;
use PicPerf\Breakdown\Lib\RequestBreakdownBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PicPerf\Breakdown\Services\RequestBreakdownService;


class BreakDown extends Command
{
    protected function configure()
    {
        $this
            ->setName('breakdown')
            ->addArgument('url', InputArgument::REQUIRED)
            ->setDescription('Break down an HTTP request.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = new RequestBreakdownService();
        $viewBuilder = new BreakdownViewBuilder();

        $viewBuilder->buildView(
            $service->analyze($input->getArgument('url'))
        );

        return Command::SUCCESS;
    }
}
