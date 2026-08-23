<?php

declare(strict_types=1);

namespace Quillstack\Standards;

use Quillstack\Standards\Exceptions\NotAPackageException;

/**
 * The package being checked, and the few questions every check asks of it.
 *
 * Nothing here knows which language it is: a name, a directory, some files. What is
 * language-specific lives in the manifest, and only the checks that need it look there.
 */
final class Package
{
    /**
     * @var array<string, mixed>
     */
    private array $manifest;

    public function __construct(public readonly string $path)
    {
        $composer = $this->path . '/composer.json';

        if (!is_file($composer)) {
            throw new NotAPackageException(
                "No composer.json in `{$this->path}`. Point this at the root of a package."
            );
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($composer), true) ?: [];
        $this->manifest = $manifest;
    }

    /**
     * The short name: `dotenv-expand` for `quillstack/dotenv-expand`, which is also the
     * repository name, the Sonar key and the page on the site.
     */
    public function name(): string
    {
        $full = is_string($this->manifest['name'] ?? null) ? $this->manifest['name'] : '';
        $parts = explode('/', $full);

        return end($parts) ?: basename($this->path);
    }

    public function fullName(): string
    {
        return is_string($this->manifest['name'] ?? null) ? $this->manifest['name'] : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        return $this->manifest;
    }

    public function has(string $file): bool
    {
        return is_file($this->path . '/' . $file);
    }

    public function read(string $file): string
    {
        $full = $this->path . '/' . $file;

        return is_file($full) ? (string) file_get_contents($full) : '';
    }
}
