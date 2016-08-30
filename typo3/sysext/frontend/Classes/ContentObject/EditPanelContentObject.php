<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
 * Contains EDITPANEL class object.
 */
class EditPanelContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, EDITPANEL
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        $theValue = '';
        if ($GLOBALS['TSFE']->beUserLogin) {
            $theValue = $this->cObj->editPanel($theValue, $conf);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
