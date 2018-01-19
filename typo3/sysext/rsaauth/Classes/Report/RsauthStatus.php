<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Rsaauth\Report;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Get status if EXT:rsaauth is still used
 */
class RsauthStatus implements StatusProviderInterface
{

    /**
     * Get status information
     *
     * @return array
     */
    public function getStatus(): array
    {
        $statuses = [
            'rsauth' => $this->getRsaAuthStatus(),
        ];
        return $statuses;
    }

    protected function getRsaAuthStatus(): ReportStatus
    {
        if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            $message = $this->getLanguageService()->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang_report.xlf:report.sslActive');
        } else {
            $message = $this->getLanguageService()->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang_report.xlf:report.sslInactive');
        }
        return GeneralUtility::makeInstance(
            ReportStatus::class,
            $this->getLanguageService()->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang_report.xlf:report.title'),
            $this->getLanguageService()->sL('LLL:EXT:rsaauth/Resources/Private/Language/locallang_report.xlf:report.description'),
            $message,
            ReportStatus::WARNING
        );
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
