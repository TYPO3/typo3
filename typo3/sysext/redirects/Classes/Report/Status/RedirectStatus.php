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

namespace TYPO3\CMS\Redirects\Report\Status;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs checks regarding redirects
 */
class RedirectStatus implements StatusProviderInterface
{
    /**
     * Determines the status of the FAL index.
     *
     * @return array List of statuses
     */
    public function getStatus(): array
    {
        return [
            'Conflicts' => $this->getConflictingRedirects(),
        ];
    }

    protected function getConflictingRedirects(): Status
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects.none');
        $severity = Status::OK;

        $registry = GeneralUtility::makeInstance(Registry::class);
        $reportedConflicts = $registry->get('tx_redirects', 'conflicting_redirects', []);
        $count = count($reportedConflicts);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:redirects/Resources/Private/Templates/Report/RedirectStatus.html'));

        if ($count > 0) {
            $value = sprintf($this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects.count'), $count);
            $severity = Status::WARNING;
        }

        $view->assignMultiple([
            'count' => $count,
            'reportedConflicts' => $reportedConflicts,
        ]);

        return GeneralUtility::makeInstance(
            Status::class,
            $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects'),
            $value,
            $view->render(),
            $severity
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
