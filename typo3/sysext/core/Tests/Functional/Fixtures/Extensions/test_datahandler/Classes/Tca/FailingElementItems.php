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

namespace TYPO3Tests\TestDatahandler\Tca;

/**
 * Items processor that refuses to run, the way one written for the editing form of its own
 * table does when it is handed a record the form never compiled.
 */
class FailingElementItems
{
    public function getItems($params): void
    {
        throw new \RuntimeException('This processor only runs in the form it was written for', 1789669201);
    }
}
