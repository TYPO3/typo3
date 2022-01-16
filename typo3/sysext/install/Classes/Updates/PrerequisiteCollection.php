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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class PrerequisiteCollection implements \IteratorAggregate
{
    /**
     * @var \ArrayObject
     */
    protected $prerequisites;

    public function __construct()
    {
        $this->prerequisites = new \ArrayObject();
    }

    /**
     * @param string $prerequisiteClass
     */
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
