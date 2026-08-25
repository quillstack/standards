<?php

declare(strict_types=1);

namespace Quillstack\Standards\Tests\Unit;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;
use Quillstack\Standards\Standard;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * The cases in `standard/conformance`, run against this checker.
 *
 * There is one set of rules and there will be a checker for every language Quillstack exists
 * in, because asking a Python developer to install PHP to check a Python package would make
 * Python the guest. Two implementations of one rule book drift — not in what the rules say,
 * which is a single file, but in how each reads them.
 *
 * These cases are what stops that. Each is a small package and a statement of what a checker
 * must object to; every checker runs all of them and must agree. A count rather than a message,
 * because the wording is a checker's own business and **what** it objects to is the rule.
 */
class TestConformance
{
    /**
     * @var string
     */
    private const SCOPES = 'universal,php';

    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cases(): array
    {
        $cases = [];

        foreach (glob(dirname(__DIR__, 2) . '/standard/conformance/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $expected = json_decode((string) file_get_contents($directory . '/expected.json'), true);

            if (is_array($expected)) {
                $cases[$directory] = $expected;
            }
        }

        return $cases;
    }

    /**
     * @return array<string, int>
     */
    private function failuresOf(string $directory): array
    {
        $package = new Package($directory);
        $failures = [];

        foreach ((new Standard())->checks(false) as $check) {
            $failures[$check->name()] = count(array_filter(
                $check->run($package),
                static fn (Finding $finding): bool => $finding->status === Finding::FAILED
            ));
        }

        return $failures;
    }

    public function everyCaseSaysWhatThisCheckerSays()
    {
        $cases = $this->cases();

        // A conformance suite which found no cases would pass in silence, which is the one
        // result it must never give.
        $this->assertBoolean->isTrue(count($cases) >= 4);

        foreach ($cases as $directory => $expected) {
            if (!str_contains(self::SCOPES, (string) ($expected['scope'] ?? 'universal'))) {
                continue;
            }

            $failures = $this->failuresOf($directory);

            foreach ($expected['checks'] as $check => $want) {
                $this->assertEqual->equal(
                    [basename($directory), $check, $want['failures']],
                    [basename($directory), $check, $failures[$check] ?? -1]
                );
            }
        }
    }

    /**
     * Every case has to say what it is for. A directory of packages nobody can read is a
     * liability rather than a specification.
     */
    public function everyCaseSaysWhatItIsFor()
    {
        foreach ($this->cases() as $directory => $expected) {
            $this->assertBoolean->isTrue(($expected['about'] ?? '') !== '');
            $this->assertBoolean->isTrue(($expected['checks'] ?? []) !== []);
        }
    }
}
