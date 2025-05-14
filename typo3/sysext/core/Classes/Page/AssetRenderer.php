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

namespace TYPO3\CMS\Core\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal The AssetRenderer is used for the asset rendering and is not public API
 */
#[Autoconfigure(public: true)]
readonly class AssetRenderer
{
    public function __construct(
        protected AssetCollector $assetCollector,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    public function renderInlineJavaScript($priority = false, ?ConsumableNonce $nonce = null): string
    {
        $this->eventDispatcher->dispatch(
            new BeforeJavaScriptsRenderingEvent($this->assetCollector, true, $priority)
        );

        $template = '<script%attributes%>%source%</script>';
        $assets = $this->assetCollector->getInlineJavaScripts($priority);
        return $this->render($assets, $template, $nonce);
    }

    public function renderJavaScript($priority = false, ?ConsumableNonce $nonce = null): string
    {
        $this->eventDispatcher->dispatch(
            new BeforeJavaScriptsRenderingEvent($this->assetCollector, false, $priority)
        );

        $template = '<script%attributes%></script>';
        $assets = $this->assetCollector->getJavaScripts($priority);
        foreach ($assets as &$assetData) {
            if (!($assetData['options']['external'] ?? false)) {
                $assetData['source'] = $this->getAbsoluteWebPath($assetData['source']);
            }
            $assetData['attributes']['src'] = $assetData['source'];
        }
        return $this->render($assets, $template, $nonce);
    }

    public function renderInlineStyleSheets($priority = false, ?ConsumableNonce $nonce = null): string
    {
        $this->eventDispatcher->dispatch(
            new BeforeStylesheetsRenderingEvent($this->assetCollector, true, $priority)
        );

        $template = '<style%attributes%>%source%</style>';
        $assets = $this->assetCollector->getInlineStyleSheets($priority);
        return $this->render($assets, $template, $nonce);
    }

    public function renderStyleSheets(bool $priority = false, string $endingSlash = '', ?ConsumableNonce $nonce = null): string
    {
        $this->eventDispatcher->dispatch(
            new BeforeStylesheetsRenderingEvent($this->assetCollector, false, $priority)
        );

        $template = '<link%attributes% ' . $endingSlash . '>';
        $assets = $this->assetCollector->getStyleSheets($priority);
        foreach ($assets as &$assetData) {
            if (!($assetData['options']['external'] ?? false)) {
                $assetData['source'] = $this->getAbsoluteWebPath($assetData['source']);
            }
            $assetData['attributes']['href'] = $assetData['source'];
            $assetData['attributes']['rel'] = $assetData['attributes']['rel'] ?? 'stylesheet';
        }
        return $this->render($assets, $template, $nonce);
    }

    protected function render(array $assets, string $template, ?ConsumableNonce $nonce = null): string
    {
        $results = [];
        foreach ($assets as $assetData) {
            $attributes = $assetData['attributes'];
            if ($nonce !== null && !empty($assetData['options']['useNonce'])) {
                $attributes['nonce'] = $nonce->consume();
            }
            $attributesString = count($attributes) ? ' ' . GeneralUtility::implodeAttributes($attributes, true) : '';
            $results[] = str_replace(
                ['%attributes%', '%source%'],
                [$attributesString, $assetData['source']],
                $template
            );
        }
        return implode(LF, $results);
    }

    private function getAbsoluteWebPath(string $file): string
    {
        if (PathUtility::hasProtocolAndScheme($file)) {
            return $file;
        }
        $file = GeneralUtility::getFileAbsFileName($file);
        $file = GeneralUtility::createVersionNumberedFilename($file);
        return PathUtility::getAbsoluteWebPath($file);
    }
}
