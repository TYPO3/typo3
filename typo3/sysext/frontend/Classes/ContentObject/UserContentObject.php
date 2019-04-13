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
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains USER class object.
 */
class UserContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, USER
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!is_array($conf) || empty($conf)) {
            $this->getTimeTracker()->setTSlogMessage('USER without configuration.', 2);
            return '';
        }
        $content = '';
        if ($this->cObj->getUserObjectType() === false) {
            // Come here only if we are not called from $TSFE->INTincScript_process()!
            $this->cObj->setUserObjectType(ContentObjectRenderer::OBJECTTYPE_USER);
        }
        $tempContent = $this->cObj->callUserFunction($conf['userFunc'], $conf, '');
        if ($this->cObj->doConvertToUserIntObject) {
            $this->cObj->doConvertToUserIntObject = false;
            $content = $this->cObj->cObjGetSingle('USER_INT', $conf);
        } else {
            $content .= $tempContent;
            // Only executed when the element is not converted to USER_INT
            if (isset($conf['stdWrap.'])) {
                $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
            }
        }
        $this->cObj->setUserObjectType(false);
        return $content;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
