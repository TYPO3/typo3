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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * General information module displaying info about the current request
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class GeneralInformation extends AbstractSubModule implements DataProviderInterface
{
    public function __construct(
        private readonly TimeTracker $timeTracker,
        private readonly Context $context,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData
    {
        $frontendUserAspect = $this->context->getAspect('frontend.user');
        return new ModuleData(
            [
                'post' => $request->getParsedBody(),
                'get' => $request->getQueryParams(),
                'cookie' => $request->getCookieParams(),
                'server' => $request->getServerParams(),
                'info' => [
                    'pageUid' => $request->getAttribute('frontend.page.information')->getId(),
                    'pageType' => $request->getAttribute('routing')->getPageType(),
                    'groupList' => implode(',', $frontendUserAspect->getGroupIds()),
                    'noCache' => !$request->getAttribute('frontend.cache.instruction')->isCachingAllowed(),
                    'noCacheReasons' => $request->getAttribute('frontend.cache.instruction')->getDisabledCacheReasons(),
                    'countUserInt' => count($request->getAttribute('frontend.controller')->config['INTincScript'] ?? []),
                    'totalParsetime' => $this->timeTracker->getParseTime(),
                    'feUser' => [
                        'uid' => $frontendUserAspect->get('id') ?: 0,
                        'username' => $frontendUserAspect->get('username') ?: '',
                    ],
                    'imagesOnPage' => $this->collectImagesOnPage($request),
                    'documentSize' => $this->collectDocumentSize($request, $response),
                ],
            ]
        );
    }

    /**
     * Creates the content for the "info" section ("module") of the Admin Panel
     */
    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);
        return $view->render('Modules/Info/General');
    }

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
     * Get image information from AssetCollector and calculates the total size.
     * Returns human-readable image sizes for fluid template output
     */
    protected function collectImagesOnPage(ServerRequestInterface $request): array
    {
        $imagesOnPage = [
            'files' => [],
            'total' => 0,
            'totalSize' => 0,
            'totalSizeHuman' => GeneralUtility::formatSize(0),
        ];
        if ($request->getAttribute('frontend.cache.instruction')->isCachingAllowed()) {
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
     * Gets the document size from the current page in a human-readable format
     */
    protected function collectDocumentSize(ServerRequestInterface $request, ResponseInterface $response): string
    {
        $documentSize = 0;
        if (!$request->getAttribute('frontend.cache.instruction')->isCachingAllowed()) {
            $documentSize = (int)$response->getBody()->getSize();
        }
        return GeneralUtility::formatSize($documentSize);
    }
}
