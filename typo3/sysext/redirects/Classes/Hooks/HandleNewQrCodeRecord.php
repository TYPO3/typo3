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

namespace TYPO3\CMS\Redirects\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Initially set values for sys_redirects of type "qrcode"
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class HandleNewQrCodeRecord
{
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, DataHandler $dataHandler): void
    {
        if ($table === 'sys_redirect' && !isset($incomingFieldArray['source_path']) && !MathUtility::canBeInterpretedAsInteger($id)) {
            $incomingFieldArray['source_path'] = StringUtility::getUniqueId('/_redirect/');
            $incomingFieldArray['keep_query_parameters'] = 1;
            $incomingFieldArray['protected'] = 1;
            $incomingFieldArray['is_regexp'] = 0;
            $incomingFieldArray['disabled'] = 0;
        }
    }
}
