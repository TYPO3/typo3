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
 * Sprite build handler
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
 */
class SpriteBuildingHandler extends AbstractSpriteHandler
{
    /**
     * @var SpriteGenerator
     */
    protected $generatorInstance = null;

    /**
     * Interface function. This will be called from the sprite manager to
     * refresh all caches.
     *
     * @return void
     */
    public function generate()
    {
        $this->generatorInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Sprite\SpriteGenerator::class, 'GeneratorHandler');
        $this->generatorInstance
            ->setOmitSpriteNameInIconName(true)
            ->setIncludeTimestampInCSS(true)
            ->setSpriteFolder(SpriteManager::$tempPath)
            ->setCSSFolder(SpriteManager::$tempPath);
        $iconsToProcess = array_merge((array)$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'], $this->collectTcaSpriteIcons());
        foreach ($iconsToProcess as $iconName => $iconFile) {
            $iconsToProcess[$iconName] = GeneralUtility::resolveBackPath('typo3/' . $iconFile);
        }
        $generatorResponse = $this->generatorInstance->generateSpriteFromArray($iconsToProcess);
        $this->iconNames = array_merge($this->iconNames, $generatorResponse['iconNames']);
        parent::generate();
    }
}
