<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * The manifest says where the documentation is, how the package is tested, and which line it is
 * released on.
 */
final class Manifest implements Check
{
    /**
     * @param string[] $requiredScripts
     * @param string[] $requiredFiles
     */
    public function __construct(
        private readonly string $homepagePattern,
        private readonly array $requiredScripts,
        private readonly bool $branchAlias,
        private readonly array $requiredFiles
    ) {
        //
    }

    public function name(): string
    {
        return 'manifest';
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
        $manifest = $package->manifest();
        $findings = [];
        $expected = str_replace('{name}', $package->name(), $this->homepagePattern);

        if (($manifest['homepage'] ?? '') !== $expected) {
            $findings[] = Finding::failed(
                $this->name(),
                'The homepage is `' . (is_string($manifest['homepage'] ?? null) ? $manifest['homepage'] : 'not set') . '`.',
                "It should be {$expected} — this package's own page, not the site root.",
            );
        }

        if (!is_string($manifest['description'] ?? null) || $manifest['description'] === '') {
            $findings[] = Finding::failed($this->name(), 'There is no description.');
        }

        /** @var array<string, mixed> $scripts */
        $scripts = is_array($manifest['scripts'] ?? null) ? $manifest['scripts'] : [];

        foreach ($this->requiredScripts as $script) {
            if (!array_key_exists($script, $scripts)) {
                $findings[] = Finding::failed($this->name(), "There is no `{$script}` script.");
            }
        }

        $extra = is_array($manifest['extra'] ?? null) ? $manifest['extra'] : [];

        if ($this->branchAlias && !isset($extra['branch-alias'])) {
            $findings[] = Finding::failed(
                $this->name(),
                'There is no branch alias.',
                'Without one Composer cannot place the development branch in a version range.'
            );
        }

        foreach ($this->requiredFiles as $file) {
            if (!$package->has($file)) {
                $findings[] = Finding::failed($this->name(), "There is no `{$file}`.");
            }
        }

        return $findings === []
            ? [Finding::passed($this->name(), 'homepage, description, scripts, alias and files all present')]
            : $findings;
    }
}
