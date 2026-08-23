<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

/**
 * The two ways a SonarCloud project is quietly wrong.
 *
 * A project imported through the interface before its first analysis arrives is created with
 * `master` as its main branch. If the repository is on `main`, every analysis lands on a short
 * branch beside the real one and the badge reports a branch nobody has ever analysed.
 *
 * And without a new code period, the quality gate is never computed at all: every condition in
 * the default gate is about new code, so there is nothing to compare against and the badge reads
 * `NOT COMPUTED` for ever.
 *
 * Both have been missed by hand five times in this project, each time noticed only because a
 * badge looked wrong. That is what this check is for.
 */
final class Quality implements Check
{
    private const API = 'https://sonarcloud.io/api';

    public function __construct(
        private readonly string $keyPattern,
        private readonly string $mainBranch,
        private readonly bool $newCodePeriodRequired,
        private readonly bool $online = false
    ) {
        //
    }

    public function name(): string
    {
        return 'quality gate';
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
        $expected = str_replace('{name}', $package->name(), $this->keyPattern);
        $findings = $this->localKey($package, $expected);

        if (!$this->online) {
            return $findings === []
                ? [Finding::passed($this->name(), "project key `{$expected}`, unchecked against SonarCloud")]
                : $findings;
        }

        return array_merge($findings, $this->remote($expected));
    }

    /**
     * @return Finding[]
     */
    private function localKey(Package $package, string $expected): array
    {
        if (!$package->has('sonar-project.properties')) {
            return [Finding::failed($this->name(), 'There is no sonar-project.properties.')];
        }

        $properties = $package->read('sonar-project.properties');

        if (preg_match('/^sonar\.projectKey=(\S+)$/m', $properties, $parts) !== 1) {
            return [Finding::failed($this->name(), 'No sonar.projectKey is set.')];
        }

        if ($parts[1] === $expected) {
            return [];
        }

        return [Finding::failed(
            $this->name(),
            "The project key is `{$parts[1]}` but this package is `{$expected}`.",
            'Analyses are being uploaded to one project while the badges read another.'
        )];
    }

    /**
     * @return Finding[]
     */
    private function remote(string $key): array
    {
        $branches = $this->get("/project_branches/list?project={$key}");

        if (!is_array($branches['branches'] ?? null)) {
            return [Finding::warning(
                $this->name(),
                "SonarCloud does not know about `{$key}`.",
                'Import the repository, then run this again.'
            )];
        }

        $findings = [];
        $main = null;

        foreach ($branches['branches'] as $branch) {
            if (is_array($branch) && ($branch['isMain'] ?? false) === true) {
                $main = $branch;
            }
        }

        if (!is_array($main)) {
            return [Finding::failed($this->name(), "`{$key}` has no main branch.")];
        }

        $branch = is_string($main['name'] ?? null) ? $main['name'] : '';
        $status = is_array($main['status'] ?? null) ? $main['status'] : [];
        $gate = is_string($status['qualityGateStatus'] ?? null) ? $status['qualityGateStatus'] : null;

        if ($branch !== $this->mainBranch) {
            $findings[] = Finding::failed(
                $this->name(),
                "The main branch in SonarCloud is `{$branch}`, not `{$this->mainBranch}`.",
                'Delete the short branch, rename the main one, and re-analyse. Until then the '
                . 'badge reports a branch nobody has analysed.'
            );
        }

        if ($this->newCodePeriodRequired && $gate === null) {
            $findings[] = Finding::failed(
                $this->name(),
                'The quality gate is not computed.',
                'Set the new code period: every condition in the default gate is about new code, '
                . 'so without one there is nothing to compare against.'
            );
        }

        return $findings === []
            ? [Finding::passed($this->name(), "`{$key}` on `{$this->mainBranch}`, gate " . strtolower($gate ?? '?'))]
            : $findings;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        $handle = curl_init(self::API . $path);

        if ($handle === false) {
            return [];
        }

        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $body = curl_exec($handle);

        /** @var array<string, mixed> $decoded */
        $decoded = is_string($body) ? (json_decode($body, true) ?: []) : [];

        return $decoded;
    }
}
