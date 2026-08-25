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

        if ($this->branchAlias) {
            $findings = array_merge($findings, $this->alias($package, $extra));
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

    /**
     * The alias has to name the line the tags are on.
     *
     * Checking only that one is there let fifteen packages carry `0.6.x-dev` while their tags
     * had moved on as far as `0.13.0`. Composer reads the alias to decide what version the
     * development branch is, so a stale one puts `dev-main` in a range nobody is asking for,
     * and it does so quietly.
     *
     * @param array<string, mixed> $extra
     *
     * @return Finding[]
     */
    private function alias(Package $package, array $extra): array
    {
        $aliases = is_array($extra['branch-alias'] ?? null) ? $extra['branch-alias'] : [];
        $alias = $aliases['dev-main'] ?? null;

        if (!is_string($alias)) {
            return [Finding::failed(
                $this->name(),
                'There is no branch alias.',
                'Without one Composer cannot place the development branch in a version range.'
            )];
        }

        $tag = self::newestTag($package);

        if ($tag === null) {
            return [];
        }

        $line = implode('.', array_slice(explode('.', ltrim($tag, 'vV')), 0, 2));
        $expected = "{$line}.x-dev";

        if ($alias === $expected) {
            return [];
        }

        return [Finding::failed(
            $this->name(),
            "The branch alias is `{$alias}`, and the newest tag is `{$tag}`.",
            "Composer reads the alias to decide what `dev-main` is, so it wants `{$expected}`."
        )];
    }

    /**
     * The newest tag here, by version rather than by name — `v0.9.1` sorts after `v0.13.0`
     * alphabetically and before it in every way that matters.
     */
    private static function newestTag(Package $package): ?string
    {
        if (!is_dir($package->path . '/.git')) {
            return null;
        }

        $command = sprintf('git -C %s tag --sort=-v:refname 2>/dev/null', escapeshellarg($package->path));
        $tags = array_filter(explode("\n", (string) shell_exec($command)));

        return $tags === [] ? null : trim((string) reset($tags));
    }
}
