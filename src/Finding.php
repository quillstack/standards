<?php

declare(strict_types=1);

namespace Quillstack\Standards;

/**
 * One thing that is not as the standard says, and what to do about it.
 *
 * A finding nobody can act on is a complaint. Every one carries the rule it comes from and the
 * remedy, because the reader is usually somebody meeting this package for the first time.
 */
final class Finding
{
    public const FAILED = 'failed';

    public const WARNING = 'warning';

    public const PASSED = 'passed';

    public function __construct(
        public readonly string $check,
        public readonly string $status,
        public readonly string $message,
        public readonly string $remedy = ''
    ) {
        //
    }

    public static function failed(string $check, string $message, string $remedy = ''): self
    {
        return new self($check, self::FAILED, $message, $remedy);
    }

    public static function warning(string $check, string $message, string $remedy = ''): self
    {
        return new self($check, self::WARNING, $message, $remedy);
    }

    public static function passed(string $check, string $message): self
    {
        return new self($check, self::PASSED, $message);
    }
}
