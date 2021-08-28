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

namespace TYPO3\CMS\Extensionmanager\Report;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Package\ComposerDeficitDetector;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * Extension composer status reports
 *
 * @internal This class is a specific EXT:reports implementation and is not part of the Public TYPO3 API.
 */
class ExtensionComposerStatus implements RequestAwareStatusProviderInterface
{
    /**
     * @var ComposerDeficitDetector
     */
    protected $composerDeficitDetector;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    public function __construct(ComposerDeficitDetector $composerDeficitDetector, UriBuilder $uriBuilder)
    {
        $this->composerDeficitDetector = $composerDeficitDetector;
        $this->uriBuilder = $uriBuilder;
    }

    public function getStatus(ServerRequestInterface $request = null): array
    {
        $status = [];
        $extensionsWithComposerDeficit = $this->composerDeficitDetector->getExtensionsWithComposerDeficit();
        $languageService = $this->getLanguageService();
        $languageService->includeLLFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
        $labelPrefix = 'report.status.composerManifest.';
        $deficits = [
            ComposerDeficitDetector::EXTENSION_COMPOSER_MANIFEST_MISSING => 'composerJsonMissing',
            ComposerDeficitDetector::EXTENSION_KEY_MISSING => 'extensionKeyMissing',
        ];

        $queryParameters = [
            'tx_extensionmanager_tools_extensionmanagerextensionmanager' => [
                'action' => 'list',
                'controller' => 'ExtensionComposerStatus',
            ],
        ];

        if ($request !== null) {
            $queryParameters['tx_extensionmanager_tools_extensionmanagerextensionmanager']['returnUrl'] =
                $request->getAttribute('normalizedParams')->getRequestUri();
        }

        $dispatchAction = 'TYPO3.ModuleMenu.showModule';
        $dispatchArgs = [
            'tools_ExtensionmanagerExtensionmanager',
            '&' . http_build_query($queryParameters),
        ];

        foreach ($deficits as $key => $deficit) {
            $message = '';
            $extensionsToReport = count(array_filter($extensionsWithComposerDeficit, static function ($extensionInformation) use ($key) {
                return $extensionInformation['deficit'] === $key;
            }));

            if ($extensionsToReport) {
                $title = $languageService->getLL($labelPrefix . 'update');
                $attributes = GeneralUtility::implodeAttributes([
                    'type' => 'button',
                    'title' => $title,
                    'class' => 'btn btn-link p-0 text-decoration-underline',
                    // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
                    'data-dispatch-action' => $dispatchAction,
                    'data-dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute($dispatchArgs),
                ], true);
                $message = sprintf($languageService->getLL($labelPrefix . $deficit . '.message'), $extensionsToReport)
                    . '<br /><button ' . $attributes . '>' . htmlspecialchars($title) . '</button>';
            }

            $status[] = GeneralUtility::makeInstance(
                ReportStatus::class,
                $this->getLanguageService()->getLL($labelPrefix . $deficit),
                $this->getLanguageService()->getLL($extensionsToReport ? 'status_checkFailed' : 'status_none'),
                $message,
                $extensionsToReport ? ReportStatus::WARNING : ReportStatus::OK
            );
        }

        return $status;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
