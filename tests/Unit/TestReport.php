<?php

declare(strict_types=1);

namespace Quillstack\Standards\Tests\Unit;

use Quillstack\Output\Output;
use Quillstack\Standards\Finding;
use Quillstack\Standards\Report;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestReport
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    public function itCountsWhatItWasGiven()
    {
        $report = new Report([
            Finding::passed('a', 'fine'),
            Finding::warning('b', 'have a look'),
            Finding::failed('c', 'no', 'do this'),
        ]);

        $this->assertEqual->equal(1, $report->count(Finding::PASSED));
        $this->assertEqual->equal(1, $report->count(Finding::WARNING));
        $this->assertEqual->equal(1, $report->count(Finding::FAILED));
        $this->assertBoolean->isTrue($report->failed());
    }

    /**
     * A warning is something to look at, not something to stop for.
     */
    public function aWarningIsNotAFailure()
    {
        $this->assertBoolean->isFalse((new Report([Finding::warning('a', 'hmm')]))->failed());
    }

    public function theRemedyIsWrittenOutWithTheFinding()
    {
        ob_start();
        (new Report([Finding::failed('badges', 'no license badge', 'add it')]))
            ->write(new Output(decorated: false), 'quillstack/example');
        $written = (string) ob_get_clean();

        $this->assertBoolean->isTrue(str_contains($written, 'quillstack/example'));
        $this->assertBoolean->isTrue(str_contains($written, 'no license badge'));
        $this->assertBoolean->isTrue(str_contains($written, 'add it'));
    }
}
