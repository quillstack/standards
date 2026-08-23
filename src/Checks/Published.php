<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * The newest tag here is one somebody can install.
 *
 * Tagging is not publishing: Packagist hears about a tag through a webhook, and a webhook can
 * fail. One did, on this project, and answered 500 — so a release looked done, CI was green, the
 * tag was on GitHub, and nobody could install it. It went unnoticed long enough for the *next*
 * release to be missing too.
 *
 * Nothing on the machine can tell you that. This asks the registry.
 */
final class Published implements Check
{
    private const REGISTRY = 'https://repo.packagist.org/p2/';

    public function __construct(private readonly bool $online = false)
    {
        //
    }

    public function name(): string
    {
        return 'published';
    }

    public function needsNetwork(): bool
    {
        return $this->online;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Package $package): array
    {
        $tag = $this->newestTag($package);

        if ($tag === null) {
            return [Finding::warning($this->name(), 'There are no tags here yet.')];
        }

        // Only the registry can answer this, so offline there is nothing to say beyond what
        // was tagged here.
        if (!$this->online) {
            return [Finding::passed($this->name(), "{$tag} is the newest tag, unchecked against Packagist")];
        }

        $published = $this->published($package->fullName());

        if ($published === []) {
            return [Finding::failed(
                $this->name(),
                "Packagist has nothing for `{$package->fullName()}`.",
                'Either it was never submitted, or the name there does not match this one.'
            )];
        }

        if (in_array($tag, $published, true)) {
            return [Finding::passed($this->name(), "{$tag} is on Packagist")];
        }

        return [Finding::failed(
            $this->name(),
            "`{$tag}` is tagged here and Packagist's newest is `{$published[0]}`.",
            'Tagging is not publishing. Check the Packagist webhook on the repository for a '
            . 'delivery that failed, and redeliver it.'
        )];
    }

    /**
     * The newest tag in the repository, by version rather than by date — a patch on an older
     * line is not the newest thing here.
     */
    private function newestTag(Package $package): ?string
    {
        if (!is_dir($package->path . '/.git')) {
            return null;
        }

        $command = sprintf(
            'git -C %s tag --sort=-v:refname 2>/dev/null',
            escapeshellarg($package->path)
        );

        $tags = array_filter(explode("\n", (string) shell_exec($command)));

        return $tags === [] ? null : trim((string) reset($tags));
    }

    /**
     * Every version the registry knows, newest first.
     *
     * The address is given a throwaway parameter because Packagist's metadata sits behind a
     * cache which holds for about a quarter of an hour, and a check that reads a stale answer
     * is worse than no check.
     *
     * @return string[]
     */
    private function published(string $name): array
    {
        if ($name === '') {
            return [];
        }

        $handle = curl_init(self::REGISTRY . $name . '.json?' . bin2hex(random_bytes(8)));

        if ($handle === false) {
            return [];
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Cache-Control: no-cache'],
        ]);

        $body = curl_exec($handle);

        if (!is_string($body)) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true) ?: [];
        $packages = is_array($decoded['packages'] ?? null) ? $decoded['packages'] : [];
        $versions = is_array($packages[$name] ?? null) ? $packages[$name] : [];
        $found = [];

        foreach ($versions as $version) {
            if (is_array($version) && is_string($version['version'] ?? null)) {
                $found[] = $version['version'];
            }
        }

        return $found;
    }
}
