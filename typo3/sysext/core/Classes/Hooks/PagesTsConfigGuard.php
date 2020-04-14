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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Guard to only allow modifications of pages.TSconfig for admin users.
 */
class PagesTsConfigGuard
{
    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param string $id
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        string $id,
        DataHandler $dataHandler
    ): void {
        if ($table === 'pages' && !$dataHandler->admin) {
            unset($incomingFieldArray['TSconfig']);
            unset($incomingFieldArray['tsconfig_includes']);
        }
    }
}
