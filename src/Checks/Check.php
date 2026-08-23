<?php

declare(strict_types=1);

namespace Quillstack\Standards\Checks;

use Quillstack\Standards\Finding;
use Quillstack\Standards\Package;

interface Check
{
    /**
     * What this check is called in the report.
     */
    public function name(): string;

    /**
     * Whether it needs the network. Everything that can be answered from the files on disk is
     * answered from them, so the common case is fast and works on a train.
     */
    public function needsNetwork(): bool;

    /**
     * @return Finding[]
     */
    public function run(Package $package): array;
}
