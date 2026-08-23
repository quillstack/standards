<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * Markdown which reads correctly and renders wrongly.
 *
 * A README here is wrapped at a hundred characters, so a sentence breaks wherever it happens to
 * reach the margin. Where an inline element lands at the end of a line and a link starts the
 * next, the renderer drops the space between them: `**with**` and `[a link]` come out as
 * `witha link`. A plain word before a link is fine, and bold before plain text is fine — it is
 * only the two together, across the break, which is exactly the kind of thing nobody spots
 * while writing and everybody spots while reading.
 */
final class Rendering implements Check
{
    public function name(): string
    {
        return 'rendering';
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
        $lines = explode("\n", $package->read('README.md'));
        $findings = [];
        $inCode = false;

        foreach ($lines as $number => $line) {
            if (str_starts_with(trim($line), '```')) {
                $inCode = !$inCode;

                continue;
            }

            if ($inCode || !isset($lines[$number + 1])) {
                continue;
            }

            $ends = preg_match('/(\*\*[^*]+\*\*|`[^`]+`)\s*$/', $line) === 1;
            $next = preg_match('/^\s*\[/', $lines[$number + 1]) === 1;

            if ($ends && $next) {
                $findings[] = Finding::failed(
                    $this->name(),
                    'README.md:' . ($number + 1) . ' ends with bold or code, and a link opens the next line.',
                    'The space between them is lost when this renders. Rewrap the sentence so the '
                    . 'two are not separated by the line break.'
                );
            }
        }

        return $findings === []
            ? [Finding::passed($this->name(), 'nothing that reads correctly and renders wrongly')]
            : $findings;
    }
}
