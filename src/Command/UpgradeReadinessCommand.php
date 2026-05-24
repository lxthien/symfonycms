<?php

namespace App\Command;

use App\Upgrade\UpgradeReadinessChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class UpgradeReadinessCommand extends Command
{
    private $checker;

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct('app:upgrade-readiness');
        $this->checker = new UpgradeReadinessChecker($kernel->getProjectDir());
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Audits known blockers before upgrading Symfony 3.3 toward 4.4/5.4/6.4.')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target Symfony version for hard blocker checks.', '4.4');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $target = (string) $input->getOption('target');
        $findings = $this->checker->check($target);
        $summary = $this->checker->summarize($findings);
        $rows = array();

        foreach ($findings as $finding) {
            $rows[] = array(strtoupper($finding['level']), $finding['area'], $finding['message']);
        }

        $io->title('Symfony Upgrade Readiness');
        $io->text('Target path: 3.3 -> 3.4 -> 4.4 -> 5.4 -> 6.4');
        $io->text('Selected target for hard blockers: ' . $target);
        $io->table(array('Level', 'Area', 'Message'), $rows);
        $io->section('Summary');
        $io->listing(array(
            'Errors: ' . $summary['error'],
            'Warnings: ' . $summary['warning'],
            'Info: ' . $summary['info'],
            'OK: ' . $summary['ok'],
        ));

        if ($summary['error'] > 0) {
            $io->error('Resolve errors before starting the Symfony 3.4/4.4 composer upgrade.');

            return 1;
        }

        if ($summary['warning'] > 0) {
            $io->success('No hard blockers detected. Warnings remain and should be handled phase by phase.');
        } else {
            $io->success('No hard blockers or warnings detected for the selected target.');
        }

        return 0;
    }
}
