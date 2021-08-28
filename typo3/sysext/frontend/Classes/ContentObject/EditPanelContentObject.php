<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * Contains EDITPANEL class object.
 * @deprecated since v11, will be removed with v12. Drop together with other editPanel removals.
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
        if ($GLOBALS['TSFE']->isBackendUserLoggedIn()) {
            $theValue = $this->cObj->editPanel($theValue, $conf);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
