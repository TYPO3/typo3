<?php
namespace TYPO3\CMS\Dbal\Report;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * A checker which hooks into the backend module "Reports" warning about dbal.
 */
class DbalStatus implements StatusProviderInterface
{
    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return array
     */
    public function getStatus()
    {
        return [
            'dbalExtensionIsInstalled' => $this->dbalExtensionIsInstalled()
        ];
    }

    /**
     * Warn about ext:dbal and give a hint when to unload.
     *
     * @return Status
     */
    protected function dbalExtensionIsInstalled()
    {
        $value = 'DBAL is loaded';
        $message = 'The Database Abstraction Layer Extension "ext:dbal" is loaded.<br />'
            . 'Unload this extension together with "ext:adodb".<br />'
            . ' Keep dbal and adodb loaded only if TYPO3 does NOT run on MySQL or MariaDB, AND if'
            . ' an old extension is loaded that uses the legacy DatabaseConnection / $GLOBALS[\'TYPO3_DB\'] query interface.<br />'
            . ' Database abstraction has been built directly into TYPO3 CMS version 8 and ext:dbal is obsolete.'
            . ' The new implementation based on doctrine dbal is much more reliable and extensions still using TYPO3_DB should'
            . ' be migrated, especially if this instance actively uses connections to databases other than MySQL or MariaDB.';
        $status = Status::WARNING;
        return GeneralUtility::makeInstance(Status::class, 'DBAL Extension', $value, $message, $status);
    }
}
