<?php

declare(strict_types=1);

namespace Quillstack\Standards\Commands;

use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\Input;
use Quillstack\Output\OutputInterface;
use Quillstack\Standards\Exceptions\NotAPackageException;
use Quillstack\Standards\Package;
use Quillstack\Standards\Report;
use Quillstack\Standards\Standard;

/**
 * Checks one package against the standard.
 *
 * Everything that can be answered from the files on disk is answered from them, so the usual
 * case is fast and works without a network. `--online` adds the questions only a service can
 * answer: whether the badges render, and whether SonarCloud is configured the way it has to be
 * for the gate to be computed at all.
 */
final class CheckCommand implements CommandInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'check';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Checks a package against the Quillstack standard';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $online = $input->hasOption('online');

        try {
            $package = new Package($input->getArgument(0) ?? (getcwd() ?: '.'));
        } catch (NotAPackageException $exception) {
            $output->writeln('  ' . $exception->getMessage());

            return 1;
        }

        $findings = [];

        foreach ((new Standard())->checks($online) as $check) {
            if ($check->needsNetwork() && !$online) {
                continue;
            }

            $findings = array_merge($findings, $check->run($package));
        }

        $report = new Report($findings);
        $report->write($output, $package->fullName() ?: $package->name());

        if (!$online) {
            $output->writeln('  Run with --online to check the badges and the quality gate too.');
        }

        return $report->failed() ? 1 : 0;
    }
}
