<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;

/**
 * Correlation scope of one Extbase persistence run. Everything written while a single
 * commit is in progress shares this scope, the same way a DataHandler run shares the
 * correlation id of its outermost instance. Backend starts a new scope per commit, so
 * long running processes do not pile up unrelated changes below one id.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final class PersistenceCorrelationScope
{
    private ?CorrelationId $correlationId = null;

    public function __construct(private readonly Random $random) {}

    /**
     * The scope is created with the first caller of a run, so a commit that writes no
     * history does not consume one.
     */
    public function get(): CorrelationId
    {
        return $this->correlationId ??= CorrelationId::forScope(
            $this->random->generateRandomBase64String(32)
        );
    }

    public function reset(): void
    {
        $this->correlationId = null;
    }
}
