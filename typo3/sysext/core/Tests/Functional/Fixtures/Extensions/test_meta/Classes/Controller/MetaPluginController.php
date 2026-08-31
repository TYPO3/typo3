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

namespace TYPO3Tests\TestMeta\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3Tests\TestMeta\PageTitle\CustomPageTitleProvider;

// User functions are resolved with GeneralUtility::makeInstance(), which only
// consults the container for public services.
#[Autoconfigure(public: true)]
class MetaPluginController
{
    public function __construct(
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry,
        private readonly CustomPageTitleProvider $pageTitleProvider,
    ) {}

    #[AsAllowedCallable]
    public function setMetaData(string $content, array $configuration, ServerRequestInterface $request): string
    {
        $pageId = $request->getQueryParams()['id'];
        if (!empty($configuration['setTitle'])) {
            $this->pageTitleProvider->setTitle('static title with pageId: ' . $pageId . ' and pluginNumber: ' . $configuration['pluginNumber']);
        }
        $metaTagManager = $this->metaTagManagerRegistry->getManagerForProperty('og:title');
        $metaTagManager->addProperty(
            'og:title',
            'OG title from a controller with pageId: ' . $pageId . ' and pluginNumber: ' . $configuration['pluginNumber'],
            [],
            true
        );
        return 'TYPO3Tests\TestMeta\Controller::setMetaData';
    }
}
