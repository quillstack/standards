<?php

declare(strict_types=1);

namespace Quillstack\Standards\Tests\Unit;

use Quillstack\Standards\Checks\PinnedActions;
use Quillstack\Standards\Checks\ReadmeSections;
use Quillstack\Standards\Checks\Rendering;
use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;
use Quillstack\Standards\Standard;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestChecks
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    private function fixture(string $name): Package
    {
        return new Package(dirname(__FILE__) . "/../Fixtures/{$name}");
    }

    /**
     * @param Finding[] $findings
     */
    private function failures(array $findings): int
    {
        return count(array_filter($findings, fn (Finding $f) => $f->status === Finding::FAILED));
    }

    public function aPackageWhichFollowsTheStandardPasses()
    {
        $standard = new Standard();
        $package = $this->fixture('good');

        foreach ($standard->checks(false) as $check) {
            if ($check->needsNetwork()) {
                continue;
            }

            $this->assertEqual->equal(0, $this->failures($check->run($package)));
        }
    }

    public function aMissingSectionIsFound()
    {
        $findings = (new ReadmeSections(
            [['title' => 'Requirements', 'required' => true]],
            true
        ))->run($this->fixture('bad'));

        $this->assertEqual->equal(1, $this->failures($findings));
    }

    /**
     * The finding says a section is at the wrong level rather than missing, because a README
     * written before the standard settled has all of them — one heading deeper.
     */
    public function aSectionAtTheWrongLevelSaysSo()
    {
        $findings = (new ReadmeSections(
            [['title' => 'Installation', 'required' => true]],
            true
        ))->run($this->fixture('bad'));

        $this->assertBoolean->isTrue(str_contains($findings[0]->message, 'sub-heading'));
    }

    /**
     * A starter skeleton merges `Installation` and `Usage` into `Getting started`, which reads
     * better than splitting them for a project that is already the project. The alternative
     * counts only for a `project`, so a library cannot reach for it.
     */
    public function aProjectSkeletonMaySayGettingStartedInstead()
    {
        $sections = [
            ['title' => 'Installation', 'required' => true, 'satisfiedBy' => ['project' => 'Getting started']],
            ['title' => 'Usage', 'required' => true, 'satisfiedBy' => ['project' => 'Getting started']],
        ];

        $findings = (new ReadmeSections($sections, true))->run($this->fixture('skeleton'));

        $this->assertEqual->equal(0, $this->failures($findings));
    }

    public function aLibraryMayNot()
    {
        $sections = [
            ['title' => 'Installation', 'required' => true, 'satisfiedBy' => ['project' => 'Getting started']],
        ];

        // The very same README, differing only in the type its manifest declares.
        $findings = (new ReadmeSections($sections, true))->run($this->fixture('skeleton-as-library'));

        $this->assertEqual->equal(1, $this->failures($findings));
    }

    public function anActionPinnedToATagIsFound()
    {
        $findings = (new PinnedActions())->run($this->fixture('bad'));

        $this->assertBoolean->isTrue($this->failures($findings) > 0);
    }

    /**
     * The one that reads correctly and renders wrongly.
     */
    public function boldMeetingALinkAcrossALineBreakIsFound()
    {
        $findings = (new Rendering())->run($this->fixture('bad'));

        $this->assertEqual->equal(1, $this->failures($findings));
        $this->assertBoolean->isTrue(str_contains($findings[0]->message, 'README.md:'));
    }

    /**
     * A fenced block is not prose, and markdown inside one is not rendered.
     */
    public function theSameThingInsideACodeBlockIsNot()
    {
        $findings = (new Rendering())->run($this->fixture('good'));

        $this->assertEqual->equal(0, $this->failures($findings));
    }
}
