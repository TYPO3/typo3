<?php
namespace TYPO3\CMS\Extbase\Utility;

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

/**
 * A checker which hooks into the backend module "Reports" checking whether
 * dbal is installed
 */
class ExtbaseRequirementsCheckUtility implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return array
     */
    public function getStatus()
    {
        $reports = [
            'dbalExtensionIsInstalled' => $this->checkIfDbalExtensionIsInstalled()
        ];
        return $reports;
    }

    /**
     * Check whether dbal extension is installed
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function checkIfDbalExtensionIsInstalled()
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')) {
            $value = 'DBAL is loaded';
            $message = 'The Database Abstraction Layer Extension (dbal) is loaded. Extbase does not fully support dbal at the moment. If you are aware of this fact or don\'t make use of the incompatible parts on this installation, you can ignore this notice.';
            $status = \TYPO3\CMS\Reports\Status::INFO;
        } else {
            $value = 'DBAL is not loaded';
            $message = '';
            $status = \TYPO3\CMS\Reports\Status::OK;
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, 'DBAL Extension', $value, $message, $status);
    }
}
