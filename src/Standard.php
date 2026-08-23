<?php

declare(strict_types=1);

namespace Quillstack\Standards;

use Quillstack\Standards\Checks\Badges;
use Quillstack\Standards\Checks\Check;
use Quillstack\Standards\Checks\Manifest;
use Quillstack\Standards\Checks\PinnedActions;
use Quillstack\Standards\Checks\Quality;
use Quillstack\Standards\Checks\ReadmeSections;
use Quillstack\Standards\Checks\Rendering;

/**
 * The rules, and the checks built from them.
 *
 * The rules are a JSON file with no PHP in it, because this framework is going to exist in more
 * than one language and a rule kept in two places is a rule that disagrees with itself. What is
 * here is a reader for them; the Python side will have its own reader and the same file.
 */
final class Standard
{
    /**
     * @var array<string, mixed>
     */
    private array $rules;

    public function __construct(?string $path = null)
    {
        $path ??= __DIR__ . '/../standard/rules.json';

        /** @var array<string, mixed> $rules */
        $rules = json_decode((string) file_get_contents($path), true) ?: [];
        $this->rules = $rules;
    }

    /**
     * @return Check[]
     */
    public function checks(bool $online = false): array
    {
        return [
            new ReadmeSections(
                $this->sections(),
                (bool) $this->at('readme.ordered', true)
            ),
            new Badges(
                $this->strings('badges.required'),
                (bool) $this->at('badges.mustRender', true),
                $online
            ),
            new Manifest(
                $this->string('repository.homepage'),
                $this->strings('php.requiredScripts'),
                (bool) $this->at('php.branchAlias', true),
                $this->strings('php.files')
            ),
            new PinnedActions(),
            new Rendering(),
            new Quality(
                $this->string('quality.sonarProjectKey'),
                $this->string('quality.sonarMainBranch', 'main'),
                (bool) $this->at('quality.sonarNewCodePeriodSet', true),
                $online
            ),
        ];
    }

    /**
     * @return array<int, array{title: string, required: bool}>
     */
    private function sections(): array
    {
        $sections = [];

        /** @var array<int, mixed> $raw */
        $raw = $this->at('readme.sections', []);

        foreach ($raw as $section) {
            if (!is_array($section) || !is_string($section['title'] ?? null)) {
                continue;
            }

            $sections[] = ['title' => $section['title'], 'required' => (bool) ($section['required'] ?? false)];
        }

        return $sections;
    }

    private function string(string $path, string $default = ''): string
    {
        $value = $this->at($path, $default);

        return is_string($value) ? $value : $default;
    }

    /**
     * @return string[]
     */
    private function strings(string $path): array
    {
        /** @var array<int, mixed> $raw */
        $raw = $this->at($path, []);

        return array_values(array_filter($raw, 'is_string'));
    }

    /**
     * A value from the rules, named the way it reads in the file: `badges.required`.
     */
    private function at(string $path, mixed $default = null): mixed
    {
        $at = $this->rules;

        foreach (explode('.', $path) as $key) {
            if (!is_array($at) || !array_key_exists($key, $at)) {
                return $default;
            }

            $at = $at[$key];
        }

        return $at;
    }
}
