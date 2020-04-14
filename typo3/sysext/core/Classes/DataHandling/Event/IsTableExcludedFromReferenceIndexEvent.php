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

namespace TYPO3\CMS\Core\DataHandling\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Event to intercept if a certain table should be excluded from the Reference Index.
 * There is no need to add tables without a definition in $GLOBALS['TCA'] since
 * ReferenceIndex only handles those.
 */
final class IsTableExcludedFromReferenceIndexEvent implements StoppableEventInterface
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var bool
     */
    private $isExcluded = false;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function markAsExcluded()
    {
        $this->isExcluded = true;
    }

    public function isTableExcluded(): bool
    {
        return $this->isExcluded;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isTableExcluded();
    }
}
