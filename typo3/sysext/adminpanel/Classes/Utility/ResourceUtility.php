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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class ResourceUtility
{
    /**
     * Get additional resources (css, js) from modules and merge it to
     * one array - returns an array of full html tags
     *
     * @param ModuleInterface[] $modules
     * @param array<string, string|ConsumableNonce> $attributes
     * @return array{js: string, css: string}
     */
    public static function getAdditionalResourcesForModules(array $modules, array $attributes = [], ?ServerRequestInterface $request = null): array
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
                $subResult = self::getAdditionalResourcesForModules($module->getSubModules(), $attributes);
                $result['js'] .= $subResult['js'];
                $result['css'] .= $subResult['css'];
            }
        }
        return $result;
    }

    /**
     * Get a css tag for file - with absolute web path resolving
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    protected static function getCssTag(string $cssFileLocation, array $attributes, ?ServerRequestInterface $request = null): string
    {
        return sprintf(
            '<link %s />',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'rel' => 'stylesheet',
                'media' => 'all',
                'href' => (string)PathUtility::getSystemResourceUri($cssFileLocation, $request),
            ], true)
        );
    }

    /**
     * Get a script tag for JavaScript with absolute paths
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    protected static function getJsTag(string $jsFileLocation, array $attributes, ?ServerRequestInterface $request = null): string
    {
        return sprintf(
            '<script %s></script>',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'src' => (string)PathUtility::getSystemResourceUri($jsFileLocation, $request),
            ], true)
        );
    }

    /**
     * Return a string with tags for main admin panel resources
     *
     * @param array<string, string|ConsumableNonce> $attributes
     */
    public static function getResources(array $attributes = [], ?ServerRequestInterface $request = null): array
    {
        $jsFileLocation = 'EXT:adminpanel/Resources/Public/JavaScript/admin-panel.js';
        $js = self::getJsTag($jsFileLocation, $attributes, $request);
        $cssFileLocation = 'EXT:adminpanel/Resources/Public/Css/adminpanel.css';
        $css = self::getCssTag($cssFileLocation, $attributes, $request);

        return [
            'css' => $css,
            'js' => $js,
        ];
    }
}
