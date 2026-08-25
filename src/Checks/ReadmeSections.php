<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * The README says the same things in the same order in every package, so somebody who has read
 * one knows where to look in the next.
 */
final class ReadmeSections implements Check
{
    /**
     * @param array<int, array{title: string, required: bool, satisfiedBy?: array<string, string>}> $sections
     */
    public function __construct(private readonly array $sections, private readonly bool $ordered)
    {
        //
    }

    public function name(): string
    {
        return 'readme sections';
    }

    public function needsNetwork(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Package $package): array
    {
        if (!$package->has('README.md')) {
            return [Finding::failed($this->name(), 'There is no README.md.')];
        }

        $readme = $package->read('README.md');
        $found = [];
        preg_match_all('/^##\s+(.+)$/m', $readme, $matches);

        foreach ($matches[1] as $heading) {
            $found[] = trim($heading);
        }

        // A README written before the standard settled puts its sections at `###`, under the
        // title. Saying so is more use than reporting them missing when they are right there.
        $deeper = [];
        preg_match_all('/^#{3,6}\s+(.+)$/m', $readme, $matches);

        foreach ($matches[1] as $heading) {
            $deeper[] = trim($heading);
        }

        $type = $package->manifest()['type'] ?? null;

        $findings = $this->missing($found, $deeper, is_string($type) ? $type : 'library');
        $findings = array_merge($findings, $this->outOfOrder($found));

        if (!preg_match('/^#\s+\S/m', $readme)) {
            $findings[] = Finding::failed(
                $this->name(),
                'The README does not open with a `# Title`.'
            );
        }

        return $findings === []
            ? [Finding::passed($this->name(), count($found) . ' sections, in the order the standard sets')]
            : $findings;
    }

    /**
     * @param string[] $found
     * @param string[] $deeper
     *
     * @return Finding[]
     */
    private function missing(array $found, array $deeper, string $type): array
    {
        $findings = [];

        foreach ($this->sections as $section) {
            if (!$section['required'] || in_array($section['title'], $found, true)) {
                continue;
            }

            // A starter skeleton is not a library: there is nothing to add to a project which
            // is already the project, so `Installation` and `Usage` are one thing there and
            // splitting them would make the README worse to read, not more uniform.
            $instead = $section['satisfiedBy'][$type] ?? null;

            if ($instead !== null && in_array($instead, $found, true)) {
                continue;
            }

            $findings[] = in_array($section['title'], $deeper, true)
                ? Finding::failed(
                    $this->name(),
                    "`{$section['title']}` is a sub-heading here, not a section.",
                    'The standard puts it at `##`. This README predates that.'
                )
                : Finding::failed(
                    $this->name(),
                    "No `## {$section['title']}` section.",
                    'Where a package has nothing to say under a heading, that is usually the '
                    . 'heading it needs most.'
                );
        }

        return $findings;
    }

    /**
     * The order is part of the standard: a reader looking for how to install something should
     * not have to find out where this package decided to put it.
     *
     * @param string[] $found
     *
     * @return Finding[]
     */
    private function outOfOrder(array $found): array
    {
        if (!$this->ordered) {
            return [];
        }

        $expected = array_column($this->sections, 'title');
        $positions = [];

        foreach ($found as $heading) {
            $at = array_search($heading, $expected, true);

            if ($at !== false) {
                $positions[$heading] = $at;
            }
        }

        $sorted = $positions;
        asort($sorted);

        if (array_keys($positions) === array_keys($sorted)) {
            return [];
        }

        return [Finding::failed(
            $this->name(),
            'The sections are not in the order the standard sets: ' . implode(' → ', array_keys($positions)),
            'Expected: ' . implode(' → ', array_keys($sorted))
        )];
    }
}
