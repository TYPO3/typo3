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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Command\CheckIntegrityCommand;
use TYPO3\CMS\Redirects\Configuration\CheckIntegrityConfiguration;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs checks regarding redirects
 */
#[Autoconfigure(public: true)]
class RedirectStatus implements StatusProviderInterface, RequestAwareStatusProviderInterface
{
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly RedirectRepository $redirectRepository,
        protected readonly Registry $registry,
        protected readonly CheckIntegrityConfiguration $checkIntegrityConfiguration,
    ) {}

    /**
     * Determines the status of redirect conflicts and shows an info status in some cases
     * in case redirects:checkintegrity was not run lately.
     *
     * OK if
     *   no redirects
     *     OR
     *   no redirect conflicts
     * WARNING if
     *   conflicts found
     *
     * Additionally:
     * INFO if
     *   checkintegrity was not run and redirects exist
     *
     * @return Status[]
     */
    public function getStatus(ServerRequestInterface $request = null): array
    {
        if ($request === null) {
            return [];
        }
        if ($this->redirectRepository->countActiveRedirects() === 0) {
            return ['Conflicts' => $this->getNoRedirectsStatus($request)];
        }
        $statusArray = ['Conflicts' => $this->getConflictingRedirectsStatus($request)];
        $lastCheckIntegrityStatus = $this->getLastCheckIntegrityStatus();
        if ($lastCheckIntegrityStatus !== null) {
            $statusArray['Last checkintegrity'] = $lastCheckIntegrityStatus;
        }
        return $statusArray;
    }

    public function getLabel(): string
    {
        return 'LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:statusProvider';
    }

    protected function getNoRedirectsStatus(ServerRequestInterface $request): Status
    {
        $view = $this->backendViewFactory->create($request, ['typo3/cms-redirects']);
        $view->assignMultiple([
            'count' => 0,
            'reportedConflicts' => [],
        ]);

        return GeneralUtility::makeInstance(
            Status::class,
            $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects'),
            $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects.none'),
            $view->render('Report/RedirectStatus'),
            ContextualFeedbackSeverity::OK
        );
    }

    protected function getConflictingRedirectsStatus(ServerRequestInterface $request): Status
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects.none');
        $severity = ContextualFeedbackSeverity::OK;

        $reportedConflicts = $this->getConflictingRedirects();
        $count = count($reportedConflicts);
        if ($count > 0) {
            $value = sprintf($this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects.count'), $count);
            $severity = ContextualFeedbackSeverity::WARNING;
        }

        $view = $this->backendViewFactory->create($request, ['typo3/cms-redirects']);
        $view->assignMultiple([
            'count' => $count,
            'reportedConflicts' => $reportedConflicts,
        ]);

        return GeneralUtility::makeInstance(
            Status::class,
            $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.conflictingRedirects'),
            $value,
            $view->render('Report/RedirectStatus'),
            $severity
        );
    }

    protected function getLastCheckIntegrityStatus(): ?Status
    {
        if (!$this->checkIntegrityConfiguration->showInfoInReports) {
            return null;
        }
        $lastCheck = $this->getCheckIntegrityLastCheckTimestamp();
        $hasCheckedBefore = $lastCheck > 0;
        $checkPoint = time() - $this->checkIntegrityConfiguration->seconds;
        $lastCheckIsWithinCheckPeriod = $lastCheck >= $checkPoint;
        if (!$hasCheckedBefore || !$lastCheckIsWithinCheckPeriod) {
            return GeneralUtility::makeInstance(
                Status::class,
                $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.checkIntegrityResultState'),
                $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.checkIntegrityResultState.title'),
                $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_reports.xlf:status.checkIntegrityResultState.message'),
                ContextualFeedbackSeverity::INFO
            );
        }
        return null;
    }

    protected function getConflictingRedirects(): array
    {
        return $this->registry->get('tx_redirects', CheckIntegrityCommand::REGISTRY_KEY_CONFLICTING_REDIRECTS, []);
    }

    protected function getCheckIntegrityLastCheckTimestamp(): int
    {
        return $this->registry->get('tx_redirects', CheckIntegrityCommand::REGISTRY_KEY_LAST_TIMESTAMP_CHECK_INTEGRITY, 0);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
