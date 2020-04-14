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
 * Contains TEXT class object.
 */
class TextContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, TEXT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!is_array($conf)) {
            return '';
        }
        $content = '';
        if (isset($conf['value'])) {
            $content = $conf['value'];
            unset($conf['value']);
        }
        if (isset($conf['value.'])) {
            $content = $this->cObj->stdWrap($content, $conf['value.']);
            unset($conf['value.']);
        }
        if (!empty($conf)) {
            $content = $this->cObj->stdWrap($content, $conf);
        }
        return $content;
    }
}
