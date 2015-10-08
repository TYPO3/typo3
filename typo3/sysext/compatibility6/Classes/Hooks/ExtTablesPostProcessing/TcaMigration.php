<?php
namespace TYPO3\CMS\Compatibility6\Hooks\ExtTablesPostProcessing;

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

use TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface;
use TYPO3\Cms\Core\Utility\GeneralUtility;

/**
 * Migrate TCA that was added by extensions in ext_tables.php
 *
 * This is deprecated, all extensions should register / manipulate TCA in Configuration/TCA nowadays.
 */
class TcaMigration implements TableConfigurationPostProcessingHookInterface
{
    /**
     * Run migration dynamically a second time on *every* request.
     * This can not be cached and is slow.
     *
     * @return void
     */
    public function processData()
    {
        /** @var \TYPO3\CMS\Core\Migrations\TcaMigration $tcaMigration */
        $tcaMigration = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Migrations\TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        $messages = $tcaMigration->getMessages();
        if (!empty($messages)) {
            $context = 'ext:compatibility6 did an automatic migration of TCA during bootstrap. This costs performance on every'
                . ' call. It also means some old extensions register TCA in ext_tables.php and not in Configuration/TCA.'
                . ' Please adapt TCA accordingly until this message is not thrown anymore and unload extension compatibility6'
                . ' as soon as possible';
            array_unshift($messages, $context);
            GeneralUtility::deprecationLog(implode(LF, $messages));
        }
    }
}
