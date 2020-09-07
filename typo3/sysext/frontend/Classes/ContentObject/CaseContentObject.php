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
 * Contains CASE class object.
 */
class CaseContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, CASE
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $setCurrent = $this->cObj->stdWrapValue('setCurrent', $conf ?? []);
        if ($setCurrent) {
            $this->cObj->data[$this->cObj->currentValKey] = $setCurrent;
        }
        $key = $this->cObj->stdWrapValue('key', $conf, null);
        $key = isset($conf[$key]) && (string)$conf[$key] !== '' ? $key : 'default';
        // If no "default" property is available, then an empty string is returned
        if ($key === 'default' && !isset($conf['default'])) {
            $theValue = '';
        } else {
            $theValue = $this->cObj->cObjGetSingle($conf[$key], $conf[$key . '.'] ?? [], $key);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
