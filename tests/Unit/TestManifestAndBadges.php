<?php

declare(strict_types=1);

namespace Quillstack\Standards\Tests\Unit;

use Quillstack\Standards\Checks\Badges;
use Quillstack\Standards\Checks\Manifest;
use Quillstack\Standards\Checks\Quality;
use Quillstack\Standards\Exceptions\NotAPackageException;
use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestManifestAndBadges
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
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

    private function manifest(): Manifest
    {
        return new Manifest(
            'https://quillstack.org/packages/{name}',
            ['test', 'test:coverage', 'stan'],
            true,
            ['README.md']
        );
    }

    public function aHomepagePointingElsewhereIsFound()
    {
        $findings = $this->manifest()->run($this->fixture('bad'));

        $this->assertBoolean->isTrue(str_contains($findings[0]->message, 'homepage'));
        $this->assertBoolean->isTrue(str_contains($findings[0]->remedy, 'packages/bad'));
    }

    public function aManifestWhichFollowsTheStandardPasses()
    {
        $this->assertEqual->equal(0, $this->failures($this->manifest()->run($this->fixture('good'))));
    }

    /**
     * The short name is what the repository, the Sonar key and the page on the site all use.
     */
    public function theNameIsTheLastPartOfIt()
    {
        $this->assertEqual->equal('good', $this->fixture('good')->name());
        $this->assertEqual->equal('quillstack/good', $this->fixture('good')->fullName());
    }

    public function somethingWhichIsNotAPackageSaysSo()
    {
        $this->assertExceptions->expect(NotAPackageException::class);

        new Package(sys_get_temp_dir());
    }

    public function everyBadgeTheStandardAsksForIsLookedFor()
    {
        $badges = new Badges(['tests', 'version', 'license'], true, false);

        $this->assertEqual->equal(0, $this->failures($badges->run($this->fixture('good'))));
        $this->assertEqual->equal(3, $this->failures($badges->run($this->fixture('bad'))));
    }

    /**
     * Nothing reaches the network unless it was asked to.
     */
    public function nothingIsFetchedWithoutBeingAsked()
    {
        $this->assertBoolean->isFalse((new Badges(['tests'], true, false))->needsNetwork());
        $this->assertBoolean->isTrue((new Badges(['tests'], true, true))->needsNetwork());
        $this->assertBoolean->isFalse((new Quality('quillstack_{name}', 'main', true, false))->needsNetwork());
    }

    public function aSonarKeyNamingAnotherPackageIsFound()
    {
        $findings = (new Quality('quillstack_{name}', 'main', true, false))->run($this->fixture('bad'));

        $this->assertBoolean->isTrue($this->failures($findings) > 0);
    }

    public function aSonarKeyWhichMatchesPasses()
    {
        $findings = (new Quality('quillstack_{name}', 'main', true, false))->run($this->fixture('good'));

        $this->assertEqual->equal(0, $this->failures($findings));
    }
}
