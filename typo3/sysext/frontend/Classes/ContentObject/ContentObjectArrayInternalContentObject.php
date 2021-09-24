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

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Contains COA_INT class object.
 */
class ContentObjectArrayInternalContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, COA_INT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!is_array($conf)) {
            $this->getTimeTracker()->setTSlogMessage('No elements in this content object array (COA_INT).', LogLevel::WARNING);
            return '';
        }
        $substKey = 'INT_SCRIPT.' . $this->getTypoScriptFrontendController()->uniqueHash();
        $content = '<!--' . $substKey . '-->';
        $this->getTypoScriptFrontendController()->config['INTincScript'][$substKey] = [
            'conf' => $conf,
            'cObj' => serialize($this->cObj),
            'type' => 'COA',
        ];
        return $content;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
