<?php
namespace TYPO3\CMS\Backend\Sprite;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 sprite manager, used in BE and in FE if a BE user is logged in.
 *
 * This class builds CSS definitions of registered icons, writes TCA definitions
 * and registers sprite icons in a cache file.
 *
 * A configurable handler class does the business task.
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
 */
class SpriteManager
{
    /**
     * @var string Directory for cached sprite informations
     */
    public static $tempPath = 'typo3temp/sprites/';

    /**
     * Is sprite manager initialized
     */
    protected static $isInitialized = false;

    /**
     * Initialize sprite manager.
     * Loads registered sprite configuration from cache, or
     * rebuilds new cache before registration.
     *
     * @return void
     */
    public static function initialize()
    {
        if (!static::isInitialized()) {
            $cacheIdentifier = static::getCacheIdentifier();
            /** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
            $codeCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_core');
            if ($codeCache->has($cacheIdentifier)) {
                $codeCache->requireOnce($cacheIdentifier);
            } else {
                static::buildSpriteDataAndCreateCacheEntry();
            }
            self::$isInitialized = true;
        }
    }

    /**
     * Whether the sprite manager is initialized.
     *
     * @return bool TRUE if sprite manager is initialized
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function isInitialized()
    {
        return self::$isInitialized;
    }

    /**
     * Set up sprite icon data and create cache entry calling the registered generator.
     *
     * Stuff the compiled $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']
     * global into php code cache.
     *
     * @throws \RuntimeException
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    protected static function buildSpriteDataAndCreateCacheEntry()
    {
        $handlerClass = $GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'];
        /** @var $handler \TYPO3\CMS\Backend\Sprite\SpriteIconGeneratorInterface */
        $handler = GeneralUtility::makeInstance($handlerClass);
        // Throw exception if handler class does not implement required interface
        if (!$handler instanceof \TYPO3\CMS\Backend\Sprite\SpriteIconGeneratorInterface) {
            throw new \RuntimeException('Class ' . $handlerClass . ' in $TYPO3_CONF_VARS[BE][spriteIconGenerator_handler] ' . ' does not implement ' . \TYPO3\CMS\Backend\Sprite\SpriteIconGeneratorInterface::class, 1294586333);
        }
        // Create temp directory if missing
        if (!is_dir((PATH_site . self::$tempPath))) {
            GeneralUtility::mkdir(PATH_site . self::$tempPath);
        }
        // Generate CSS and TCA files, build icon set register
        $handler->generate();
        // Get all icons registered from skins, merge with core icon list
        $availableSkinIcons = (array)$GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'];
        if (isset($GLOBALS['TBE_STYLES']['skins']) && is_array($GLOBALS['TBE_STYLES']['skins'])) {
            foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinData) {
                $availableSkinIcons = array_merge($availableSkinIcons, (array)$skinData['availableSpriteIcons']);
            }
        }
        // Merge icon names provided by the skin, with
        // registered "complete sprites" and the handler class
        $iconNames = array_merge($availableSkinIcons, (array)$GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'], $handler->getAvailableIconNames());
        $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'] = $iconNames;

        $cacheFileContent = '$GLOBALS[\'TBE_STYLES\'][\'spriteIconApi\'][\'iconsAvailable\'] = ';
        $cacheFileContent .= var_export($iconNames, true) . ';';
        /** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_core')->set(static::getCacheIdentifier(), $cacheFileContent);
    }

    /**
     * Get cache identifier for $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']
     *
     * @return string
     */
    protected static function getCacheIdentifier()
    {
        return 'sprites_' . sha1((TYPO3_version . PATH_site . 'spriteManagement'));
    }

    /**
     * API for extensions to register own sprites.
     *
     * Get an array of icon names and the styleSheetFile with defined sprite icons.
     * The stylesheet filename should contain the extension name to be unique.
     *
     * Naming conventions:
     * - IconName: extensions-$extKey-$iconName
     * - CSS class for loading the sprite: t3-icon-extensions-$extKey
     * - CSS class for single icons: t3-icon-$extKey-$iconName
     *
     * @param array $icons Icon names
     * @param string $styleSheetFile Stylesheet filename relative to PATH_typo3. Skins do not need to supply the $styleSheetFile, if the CSS file is within the registered stylesheet folders
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function addIconSprite(array $icons, $styleSheetFile = '')
    {
        GeneralUtility::deprecationLog(self::class . ' is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8');
        $GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'] = array_merge((array)$GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'], $icons);
        if ($styleSheetFile !== '') {
            $GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'][] = $styleSheetFile;
        }
    }

    /**
     * API for extensions to register new sprite images which can be used with
     * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-$extKey-iconName');
     *
     * @param array $icons Icons to be registered, $iconname => $iconFile, $iconFile must be relative to PATH_site
     * @param string $extKey Extension key
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function addSingleIcons(array $icons, $extKey = '')
    {
        GeneralUtility::deprecationLog(self::class . ' is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8');
        foreach ($icons as $iconName => $iconFile) {
            $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extKey . '-' . $iconName] = $iconFile;
        }
    }

    /**
     * API to register new type icons for tables which use "typeicon_classes"
     * Can be used to provide icons for "modules" in pages table
     *
     * @param string $table Table name to which the type icon should be added
     * @param string $type Type column name of the table
     * @param string $iconFile Icon filename, relative to PATH_typo3
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function addTcaTypeIcon($table, $type, $iconFile)
    {
        GeneralUtility::deprecationLog(self::class . ' is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8');
        $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type] = $iconFile;
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
            $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] = 'tcarecords-' . $table . '-' . $type;
        }
    }
}
