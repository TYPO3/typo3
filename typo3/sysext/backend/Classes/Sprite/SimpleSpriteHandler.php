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

/**
 * A class with an concrete implementation of AbspractSpriteHandler.
 * It is the standard / fallback handler of the sprite manager.
 * This implementation won't generate sprites at all. It will just render css-definitions
 * for all registered icons so that they may be used through
 * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon* Without the css classes
 * generated here, icons of for example tca records would be empty.
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
 */
class SimpleSpriteHandler extends AbstractSpriteHandler
{
    /**
     * css template for single Icons registered by extension authors
     *
     * @var string
     */
    protected $styleSheetTemplateExtIcons = '
.t3-icon-###NAME### {
	background-position: 0px 0px !important;
	background-image: url(\'###IMAGE###\') !important;
	background-size: contain;
}
	';

    /**
     * Interface function. This will be called from the sprite manager to
     * refresh all caches.
     *
     * @return void
     */
    public function generate()
    {
        // Generate IconData for single Icons registered
        $this->buildCssAndRegisterIcons();
        parent::generate();
    }

    /**
     * This function builds an css class for every single icon registered via
     * \TYPO3\CMS\Backend\Utility\IconUtility::addSingleIcons to use them via
     * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon and TCA-Icons for
     * "classic" record Icons to be uses via
     * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord
     * In the simpleHandler the icon just will be added as css-background-image.
     *
     * @return void
     */
    protected function buildCssAndRegisterIcons()
    {
        // Backpath from the stylesheet file ($cssTcaFile) to PATH_site dir
        // in order to set the background-image URL paths correct
        $iconPath = '../../' . TYPO3_mainDir;
        $iconsToProcess = array_merge((array)$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'], $this->collectTcaSpriteIcons());
        foreach ($iconsToProcess as $iconName => $iconFile) {
            $css = str_replace('###NAME###', str_replace(['extensions-', 'tcarecords-'], ['', ''], $iconName), $this->styleSheetTemplateExtIcons);
            $css = str_replace('###IMAGE###', \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($iconPath . $iconFile), $css);
            $this->iconNames[] = $iconName;
            $this->styleSheetData .= $css;
        }
    }
}
