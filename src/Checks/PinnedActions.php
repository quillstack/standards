<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * Every action in a workflow is pinned to a commit, with the version in a comment beside it.
 *
 * A tag is a pointer its owner can move, and whoever controls it controls what runs in CI — with
 * a checkout of the repository and whatever secrets the job is given. The comment is what
 * Dependabot reads to know where it is starting from, so it has to be true.
 */
final class PinnedActions implements Check
{
    public function name(): string
    {
        return 'pinned actions';
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
        $directory = $package->path . '/.github/workflows';

        if (!is_dir($directory)) {
            return [Finding::warning($this->name(), 'There are no workflows here.')];
        }

        $findings = [];
        $pinned = 0;

        foreach (glob($directory . '/*.yml') ?: [] as $file) {
            foreach (explode("\n", (string) file_get_contents($file)) as $number => $line) {
                if (preg_match('/uses:\s*(\S+)@(\S+)/', $line, $parts) !== 1) {
                    continue;
                }

                [, $action, $reference] = $parts;
                $where = basename($file) . ':' . ($number + 1);

                if (preg_match('/^[0-9a-f]{40}$/', $reference) !== 1) {
                    $findings[] = Finding::failed(
                        $this->name(),
                        "{$where} uses `{$action}@{$reference}`, which is a tag or a branch.",
                        'Pin it to the full commit hash, with the version in a trailing comment.'
                    );

                    continue;
                }

                if (!str_contains($line, '#')) {
                    $findings[] = Finding::failed(
                        $this->name(),
                        "{$where} pins `{$action}` to a commit with no version comment.",
                        'Add `# v1.2.3`: it is the only part of a pinned reference a person can read.'
                    );

                    continue;
                }

                ++$pinned;
            }
        }

        return $findings === []
            ? [Finding::passed($this->name(), "{$pinned} actions pinned to commits")]
            : $findings;
    }
}
