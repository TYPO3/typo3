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
 * Contains USER_INT class object.
 */
class UserInternalContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, USER_INT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        $this->cObj->setUserObjectType(ContentObjectRenderer::OBJECTTYPE_USER_INT);
        $tsfe = $this->getTypoScriptFrontendController();
        $substKey = 'INT_SCRIPT.' . $tsfe->uniqueHash();
        $content = '<!--' . $substKey . '-->';
        $tsfe->config['INTincScript'][$substKey] = [
            'conf' => $conf,
            'cObj' => serialize($this->cObj),
            'type' => 'FUNC'
        ];
        $this->cObj->setUserObjectType(false);
        return $content;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
