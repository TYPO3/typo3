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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collection of prerequisites used internally in upgrade wizard commands.
 *
 * @internal for use in upgrade wizard command only and not part of public API.
 */
final class PrerequisiteCollection implements \IteratorAggregate
{
    private \ArrayObject $prerequisites;

    public function __construct()
    {
        $this->prerequisites = new \ArrayObject();
    }

    public function add(string $prerequisiteClass): void
    {
        if (
            !($this->prerequisites[$prerequisiteClass] ?? false)
            && is_a($prerequisiteClass, PrerequisiteInterface::class, true)
        ) {
            $this->prerequisites[$prerequisiteClass] = GeneralUtility::makeInstance(
                $prerequisiteClass
            );
        }
    }

    public function getIterator(): \Traversable
    {
        return $this->prerequisites;
    }
}
