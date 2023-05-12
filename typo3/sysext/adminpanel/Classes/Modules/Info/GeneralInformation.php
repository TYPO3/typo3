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

namespace TYPO3\CMS\Adminpanel\Modules\Info;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * General information module displaying info about the current
 * request
 *
 * @internal
 */
class GeneralInformation extends AbstractSubModule implements DataProviderInterface
{
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        /** @var UserAspect $frontendUserAspect */
        $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        $tsfe = $this->getTypoScriptFrontendController();
        return new ModuleData(
            [
                'post' => $request->getParsedBody(),
                'get' => $request->getQueryParams(),
                'cookie' => $request->getCookieParams(),
                'server' => $request->getServerParams(),
                'info' => [
                    'pageUid' => $tsfe->id,
                    'pageType' => $tsfe->getPageArguments()->getPageType(),
                    'groupList' => implode(',', $frontendUserAspect->getGroupIds()),
                    'noCache' => $this->isNoCacheEnabled(),
                    'countUserInt' => count($tsfe->config['INTincScript'] ?? []),
                    'totalParsetime' => $this->getTimeTracker()->getParseTime(),
                    'feUser' => [
                        'uid' => $frontendUserAspect->get('id') ?: 0,
                        'username' => $frontendUserAspect->get('username') ?: '',
                    ],
                    'imagesOnPage' => $this->collectImagesOnPage(),
                    'documentSize' => $this->collectDocumentSize(),
                ],
            ]
        );
    }

    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     * @see display()
     */
    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Info/General.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render();
    }

    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     */
    public function getIdentifier(): string
    {
        return 'info_general';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:sub.general.label'
        );
    }

    /**
     * Collects images from TypoScriptFrontendController and calculates the total size.
     * Returns human-readable image sizes for fluid template output
     */
    protected function collectImagesOnPage(): array
    {
        $imagesOnPage = [
            'files' => [],
            'total' => 0,
            'totalSize' => 0,
            'totalSizeHuman' => GeneralUtility::formatSize(0),
        ];

        if ($this->isNoCacheEnabled() === false) {
            return $imagesOnPage;
        }

        $count = 0;
        $totalImageSize = 0;
        foreach (GeneralUtility::makeInstance(AssetCollector::class)->getMedia() as $file => $information) {
            $filePath = Environment::getPublicPath() . '/' . ltrim(parse_url($file, PHP_URL_PATH), '/');
            $fileSize = is_file($filePath) ? filesize($filePath) : 0;
            $imagesOnPage['files'][] = [
                'name' => $file,
                'size' => $fileSize,
                'sizeHuman' => GeneralUtility::formatSize($fileSize),
            ];
            $totalImageSize += $fileSize;
            $count++;
        }
        $imagesOnPage['totalSize'] = GeneralUtility::formatSize($totalImageSize);
        $imagesOnPage['total'] = $count;
        return $imagesOnPage;
    }

    /**
     * Gets the document size from the current page in a human readable format
     */
    protected function collectDocumentSize(): string
    {
        $documentSize = 0;
        if ($this->isNoCacheEnabled() === true) {
            $documentSize = mb_strlen($this->getTypoScriptFrontendController()->content, 'UTF-8');
        }

        return GeneralUtility::formatSize($documentSize);
    }

    protected function isNoCacheEnabled(): bool
    {
        return (bool)$this->getTypoScriptFrontendController()->no_cache;
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
