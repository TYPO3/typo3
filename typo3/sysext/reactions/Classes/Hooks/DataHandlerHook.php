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

namespace TYPO3\CMS\Reactions\Hooks;

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class DataHandlerHook
{
    public function processDatamap_postProcessFieldArray($status, $table, $id, array &$fieldArray, DataHandler $dataHandler): void
    {
        // Only consider reactions
        if ($table !== 'sys_reaction') {
            return;
        }
        // Only consider new reactions
        if ($status !== 'new') {
            return;
        }
        // Create a UUID for a new reaction if non is present in the field array
        if (!isset($fieldArray['identifier'])) {
            $fieldArray['identifier'] = (string)Uuid::v4();
        }
        // Create a valid UUID for a new reaction if given identifier is invalid
        if (!Uuid::isValid($fieldArray['identifier'])) {
            $fieldArray['identifier'] = (string)Uuid::v4();
        }
    }
}
