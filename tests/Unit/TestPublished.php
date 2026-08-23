<?php

declare(strict_types=1);

namespace Quillstack\Standards\Tests\Unit;

use Quillstack\Standards\Checks\Published;
use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * Tagging is not publishing. A registry hears about a tag through a webhook, and a webhook can
 * fail — one did on this project and answered 500, so a release looked done, CI was green, the
 * tag was on GitHub, and nobody could install it. It went unnoticed long enough for the next
 * release to be missing too.
 */
class TestPublished
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
     * The registry is the only thing that can answer this, so offline it says what it knows and
     * no more — rather than reporting a package unpublished because there was no network.
     */
    public function offlineItSaysWhatItCannotKnow()
    {
        $findings = (new Published(false))->run($this->fixture('good'));

        $this->assertEqual->equal(Finding::WARNING, $findings[0]->status);
        $this->assertBoolean->isTrue(str_contains($findings[0]->message, 'no tags'));
    }

    /**
     * A directory which is not a repository has no tags to compare, which is worth saying
     * rather than failing over.
     */
    public function somethingWithNoTagsIsNotAFailure()
    {
        $findings = (new Published(true))->run($this->fixture('good'));

        $this->assertEqual->equal(Finding::WARNING, $findings[0]->status);
    }

    public function itKnowsWhetherItNeedsTheNetwork()
    {
        $this->assertBoolean->isFalse((new Published(false))->needsNetwork());
        $this->assertBoolean->isTrue((new Published(true))->needsNetwork());
    }
}
