<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Utility;

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

use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class ResourceUtility
{
    /**
     * Get additional resources (css, js) from modules and merge it to
     * one array - returns an array of full html tags
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface[] $modules
     * @return array
     */
    public static function getAdditionalResourcesForModules(array $modules): array
    {
        $result = [
            'js' => '',
            'css' => '',
        ];
        foreach ($modules as $module) {
            if ($module instanceof ResourceProviderInterface) {
                foreach ($module->getJavaScriptFiles() as $file) {
                    $result['js'] .= static::getJsTag($file);
                }
                foreach ($module->getCssFiles() as $file) {
                    $result['css'] .= static::getCssTag($file);
                }
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $subResult = self::getAdditionalResourcesForModules($module->getSubModules());
                $result['js'] .= $subResult['js'];
                $result['css'] .= $subResult['css'];
            }
        }
        return $result;
    }

    public static function getAdditionalResourcesForModule(ResourceProviderInterface $module): array
    {
        $result = [
            'js' => '',
            'css' => '',
        ];
        foreach ($module->getJavaScriptFiles() as $file) {
            $result['js'] .= static::getJsTag($file);
        }
        foreach ($module->getCssFiles() as $file) {
            $result['css'] .= static::getCssTag($file);
        }
        return $result;
    }

    /**
     * Returns a link tag with the admin panel stylesheet
     * defined using TBE_STYLES
     *
     * @return string
     */
    protected static function getAdminPanelStylesheet(): string
    {
        $result = '';
        if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
            $stylesheet = GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel']);
            $result = '<link rel="stylesheet" type="text/css" href="' .
                      htmlspecialchars($stylesheet, ENT_QUOTES | ENT_HTML5) . '" />';
        }
        return $result;
    }

    /**
     * Get a css tag for file - with absolute web path resolving
     *
     * @param string $cssFileLocation
     * @return string
     */
    protected static function getCssTag(string $cssFileLocation): string
    {
        $css = '<link type="text/css" rel="stylesheet" href="' .
               htmlspecialchars(
                   PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($cssFileLocation)),
                   ENT_QUOTES | ENT_HTML5
               ) .
               '" media="all" />';
        return $css;
    }

    /**
     * Get a script tag for JavaScript with absolute paths
     *
     * @param string $jsFileLocation
     * @return string
     */
    protected static function getJsTag(string $jsFileLocation): string
    {
        $js = '<script type="text/javascript" src="' .
              htmlspecialchars(
                  PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($jsFileLocation)),
                  ENT_QUOTES | ENT_HTML5
              ) .
              '"></script>';
        return $js;
    }

    /**
     * Return a string with tags for main admin panel resources
     *
     * @return array
     */
    public static function getResources(): array
    {
        $jsFileLocation = 'EXT:adminpanel/Resources/Public/JavaScript/AdminPanel.js';
        $js = ResourceUtility::getJsTag($jsFileLocation);
        $cssFileLocation = 'EXT:adminpanel/Resources/Public/Css/adminpanel.css';
        $css = ResourceUtility::getCssTag($cssFileLocation);

        return [
            'css' => $css . ResourceUtility::getAdminPanelStylesheet(),
            'js' => $js,
        ];
    }
}
