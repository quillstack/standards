<?php

declare(strict_types=1);

namespace Quillstack\Standards;

use Quillstack\Output\OutputInterface;

/**
 * What was checked and what came of it.
 */
final class Report
{
    /**
     * @param Finding[] $findings
     */
    public function __construct(private readonly array $findings)
    {
        //
    }

    public function failed(): bool
    {
        return $this->count(Finding::FAILED) > 0;
    }

    public function count(string $status): int
    {
        return count(array_filter($this->findings, fn (Finding $f) => $f->status === $status));
    }

    public function write(OutputInterface $output, string $package): void
    {
        $output->writeln("Checking {$package} against the Quillstack standard");
        $output->writeln('');

        foreach ($this->findings as $finding) {
            $mark = match ($finding->status) {
                Finding::PASSED => '  ok   ',
                Finding::WARNING => '  ~    ',
                default => '  FAIL ',
            };

            $output->writeln($mark . str_pad($finding->check, 16) . $finding->message);

            if ($finding->remedy !== '') {
                $output->writeln(str_repeat(' ', 25) . $finding->remedy);
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '  %d passed, %d to look at, %d failed',
            $this->count(Finding::PASSED),
            $this->count(Finding::WARNING),
            $this->count(Finding::FAILED),
        ));
    }
}
