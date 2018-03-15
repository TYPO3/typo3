<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Info Module
 */
class InfoModule extends AbstractModule
{
    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    public function getContent(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Info.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);
        $tsfe = $this->getTypoScriptFrontendController();

        $view->assignMultiple([
            'info' => [
                'pageUid' => $tsfe->id,
                'pageType' => $tsfe->type,
                'groupList' => $tsfe->gr_list,
                'noCache' => $this->isNoCacheEnabled(),
                'countUserInt' => \count($tsfe->config['INTincScript'] ?? []),
                'totalParsetime' => $this->getTimeTracker()->getParseTime(),
                'feUser' => [
                    'uid' => $tsfe->fe_user->user['uid'] ?? 0,
                    'username' => $tsfe->fe_user->user['username'] ?? ''
                ],
                'imagesOnPage' => $this->collectImagesOnPage(),
                'documentSize' => $this->collectDocumentSize()
            ]
        ]);

        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'info';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $locallangFileAndPath = 'LLL:' . $this->extResources . '/Language/locallang_info.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Collects images from TypoScriptFrontendController and calculates the total size.
     * Returns human readable image sizes for fluid template output
     *
     * @return array
     */
    private function collectImagesOnPage(): array
    {
        $imagesOnPage = [
            'files' => [],
            'total' => 0,
            'totalSize' => 0,
            'totalSizeHuman' => GeneralUtility::formatSize(0)
        ];

        if ($this->isNoCacheEnabled() === false) {
            return $imagesOnPage;
        }

        $count = 0;
        $totalImageSize = 0;
        if (!empty($this->getTypoScriptFrontendController()->imagesOnPage)) {
            foreach ($this->getTypoScriptFrontendController()->imagesOnPage as $file) {
                $fileSize = @filesize($file);
                $imagesOnPage['files'][] = [
                    'name' => $file,
                    'size' => $fileSize,
                    'sizeHuman' => GeneralUtility::formatSize($fileSize)
                ];
                $totalImageSize += $fileSize;
                $count++;
            }
        }
        $imagesOnPage['totalSize'] = GeneralUtility::formatSize($totalImageSize);
        $imagesOnPage['total'] = $count;
        return $imagesOnPage;
    }

    /**
     * Gets the document size from the current page in a human readable format
     * @return string
     */
    private function collectDocumentSize(): string
    {
        $documentSize = 0;
        if ($this->isNoCacheEnabled() === true) {
            $documentSize = \mb_strlen($this->getTypoScriptFrontendController()->content, 'UTF-8');
        }

        return GeneralUtility::formatSize($documentSize);
    }

    /**
     * @return bool
     */
    private function isNoCacheEnabled(): bool
    {
        return (bool)$this->getTypoScriptFrontendController()->no_cache;
    }
}
