<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * Every badge the standard asks for is there, and — with the network — every one of them
 * actually answers. **A badge that 404s is worse than no badge**, because it reads as a broken
 * project rather than an unconfigured service.
 */
final class Badges implements Check
{
    /**
     * How each required badge is recognised in the README. The key is the name in the standard.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'tests' => '#/actions/workflows/tests\.yml/badge\.svg#',
        'version' => '#img\.shields\.io/packagist/v/#',
        'downloads' => '#img\.shields\.io/packagist/dt/#',
        'language-version' => '#img\.shields\.io/packagist/php-v/#',
        'styleci' => '#github\.styleci\.io/repos/\d+/shield#',
        'codefactor' => '#codefactor\.io/repository/github/[^/]+/[^/]+/badge#',
        'sonar-quality-gate' => '#metric=alert_status#',
        'sonar-coverage' => '#metric=coverage#',
        'sonar-maintainability' => '#metric=sqale_rating#',
        'sonar-reliability' => '#metric=reliability_rating#',
        'sonar-security' => '#metric=security_rating#',
        'license' => '#img\.shields\.io/packagist/l/#',
    ];

    /**
     * @param string[] $required
     */
    public function __construct(
        private readonly array $required,
        private readonly bool $mustRender,
        private readonly bool $online = false
    ) {
        //
    }

    public function name(): string
    {
        return 'badges';
    }

    public function needsNetwork(): bool
    {
        return $this->online && $this->mustRender;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Package $package): array
    {
        $readme = $package->read('README.md');
        $findings = [];

        foreach ($this->required as $badge) {
            $pattern = self::PATTERNS[$badge] ?? null;

            if ($pattern === null || preg_match($pattern, $readme) === 1) {
                continue;
            }

            $findings[] = Finding::failed($this->name(), "No `{$badge}` badge.");
        }

        if ($this->needsNetwork()) {
            $findings = array_merge($findings, $this->fetch($readme));
        }

        return $findings === []
            ? [Finding::passed($this->name(), count($this->required) . ' badges' . ($this->needsNetwork() ? ', all answering' : ''))]
            : $findings;
    }

    /**
     * @return Finding[]
     */
    private function fetch(string $readme): array
    {
        preg_match_all('#\[!\[[^\]]*\]\(([^)]+)\)\]#', $readme, $matches);
        $findings = [];

        foreach (array_unique($matches[1]) as $url) {
            $status = $this->statusOf($url);

            if ($status >= 200 && $status < 400) {
                continue;
            }

            $findings[] = Finding::failed(
                $this->name(),
                "The badge at {$url} answers {$status}.",
                'Either the service has not been told about this repository, or the URL is wrong.'
            );
        }

        return $findings;
    }

    private function statusOf(string $url): int
    {
        $handle = curl_init($url);

        if ($handle === false) {
            return 0;
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOBODY => false,
        ]);
        curl_exec($handle);

        return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    }
}
