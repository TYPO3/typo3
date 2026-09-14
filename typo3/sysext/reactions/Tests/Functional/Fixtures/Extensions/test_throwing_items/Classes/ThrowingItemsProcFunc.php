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

namespace TYPO3Tests\TestThrowingItems;

/**
 * Item processors that refuse to run, the way one written for the editing form of its own
 * table does when it is handed a record that does not exist yet.
 */
final class ThrowingItemsProcFunc
{
    public function throwException(array &$params): void
    {
        throw new \RuntimeException('This processor only runs in the form it was written for', 1789430401);
    }

    /**
     * A processor is arbitrary code, and a type violation in it is an error rather than an
     * exception. ItemProcessingService catches an exception of its own, so this is the one
     * that reaches the caller.
     */
    public function throwError(array &$params): void
    {
        throw new \TypeError('This processor was handed something it cannot use', 1789430402);
    }
}
