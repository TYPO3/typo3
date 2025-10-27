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

namespace TYPO3\CMS\Adminpanel\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class ResourceUtility
{
    public function __construct(
        private SystemResourceFactory $resourceFactory,
        private SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    /**
     * Get additional resources (css, js) from modules and merge it to
     * one array - returns an array of full html tags
     *
     * @param ModuleInterface[] $modules
     * @param array<string, string|ConsumableNonce> $attributes
     * @return array{js: string, css: string}
     */
    public function getAdditionalResourcesForModules(array $modules, array $attributes, ServerRequestInterface $request): array
    {
        $result = [
            'js' => '',
            'css' => '',
        ];
        foreach ($modules as $module) {
            if ($module instanceof ResourceProviderInterface) {
                foreach ($module->getJavaScriptFiles() as $file) {
                    $result['js'] .= static::getJsTag($file, $attributes, $request);
                }
                foreach ($module->getCssFiles() as $file) {
                    $result['css'] .= static::getCssTag($file, $attributes, $request);
                }
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $subResult = $this->getAdditionalResourcesForModules($module->getSubModules(), $attributes, $request);
                $result['js'] .= $subResult['js'];
                $result['css'] .= $subResult['css'];
            }
        }
        return $result;
    }

    /**
     * Return a string with tags for main admin panel resources
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    public function getResources(array $attributes, ServerRequestInterface $request): array
    {
        $jsFileLocation = 'EXT:adminpanel/Resources/Public/JavaScript/admin-panel.js';
        $js = $this->getJsTag($jsFileLocation, $attributes, $request);
        $cssFileLocation = 'EXT:adminpanel/Resources/Public/Css/adminpanel.css';
        $css = $this->getCssTag($cssFileLocation, $attributes, $request);
        return [
            'css' => $css,
            'js' => $js,
        ];
    }

    /**
     * Get a css tag for file - with absolute web path resolving
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    protected function getCssTag(string $cssFileLocation, array $attributes, ServerRequestInterface $request): string
    {
        $resource = $this->resourceFactory->createPublicResource($cssFileLocation);
        return sprintf(
            '<link %s />',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'rel' => 'stylesheet',
                'media' => 'all',
                'href' => (string)$this->resourcePublisher->generateUri($resource, $request),
            ], true)
        );
    }

    /**
     * Get a script tag for JavaScript with absolute paths
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    protected function getJsTag(string $jsFileLocation, array $attributes, ServerRequestInterface $request): string
    {
        $resource = $this->resourceFactory->createPublicResource($jsFileLocation);
        return sprintf(
            '<script %s></script>',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'src' => (string)$this->resourcePublisher->generateUri($resource, $request),
            ], true)
        );
    }
}
