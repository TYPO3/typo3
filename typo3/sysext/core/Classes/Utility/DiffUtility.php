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

namespace TYPO3\CMS\Core\Utility;

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Word;

/**
 * This class has functions which generates a difference output of a content string
 */
class DiffUtility
{
    /**
     * If set, the HTML tags are stripped from the input strings first.
     *
     * @var bool
     */
    public $stripTags = true;

    /**
     * This will produce a color-marked-up diff output in HTML from the input strings.
     *
     * @param string $str1 String 1
     * @param string $str2 String 2
     * @return string Formatted output.
     */
    public function makeDiffDisplay($str1, $str2)
    {
        if ($this->stripTags) {
            $str1 = strip_tags($str1);
            $str2 = strip_tags($str2);
        }
        $diff = new Diff(new Word());
        return $diff->render((string)$str1, (string)$str2);
    }
}
