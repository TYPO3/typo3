<?php
namespace TYPO3\CMS\Backend\View;

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
 * class to render the TYPO3 logo in the backend
 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8 - change the logic of the logo directly in the corresponding Fluid template
 */
class LogoView
{
    /**
     * @var string
     */
    protected $logo = '';

    /**
     * Constructor
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8 - change the logic of the logo directly in the corresponding Fluid template
     */
    public function __construct()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        $this->logo = 'sysext/backend/Resources/Public/Images/typo3-topbar@2x.png';
    }

    /**
     * renders the actual logo code
     *
     * @return string Logo html code snippet to use in the backend
     */
    public function render()
    {
        $imgInfo = getimagesize(PATH_site . TYPO3_mainDir . $this->logo);
        $imgUrl = $this->logo;

        // Overwrite with custom logo
        if ($GLOBALS['TBE_STYLES']['logo']) {
            $imgInfo = @getimagesize(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath((PATH_typo3 . $GLOBALS['TBE_STYLES']['logo']), 3));
            $imgUrl = $GLOBALS['TBE_STYLES']['logo'];
        }

        // High-res?
        $width = $imgInfo[0];
        $height = $imgInfo[1];

        if (strpos($imgUrl, '@2x.')) {
            $width = $width/2;
            $height = $height/2;
        }

        $logoTag = '<img src="' . $imgUrl . '" width="' . $width . '" height="' . $height . '" title="TYPO3 Content Management System" alt="" />';
        return '<a class="typo3-topbar-site-logo" href="' . htmlspecialchars(TYPO3_URL_GENERAL) . '" target="_blank">' . $logoTag . '</a> <span class="typo3-topbar-site-name">' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . ' [' . TYPO3_version . ']</span>';
    }

    /**
     * Sets the logo
     *
     * @param string $logo Path to logo file as seen from typo3/
     * @throws \InvalidArgumentException
     */
    public function setLogo($logo)
    {
        if (!is_string($logo)) {
            throw new \InvalidArgumentException('parameter $logo must be of type string', 1194041104);
        }
        $this->logo = $logo;
    }
}
