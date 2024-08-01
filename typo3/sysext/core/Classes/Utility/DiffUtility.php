<?php

declare(strict_types=1);

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
use cogpowered\FineDiff\Granularity\Character;
use cogpowered\FineDiff\Granularity\Word;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Helper service to create a diff HTML of two strings.
 * It is currently a facade for lolli42/finediff.
 *
 * @todo Remove "shared: false" in v14 when `$this->stripTags` is dropped.
 */
#[Autoconfigure(shared: false)]
class DiffUtility
{
    /**
     * If set, the HTML tags are stripped from the input strings first.
     *
     * @deprecated will be removed in TYPO3 v14. Remove together with makeDiffDisplay().
     */
    public bool $stripTags = true;

    /**
     * Returns a color-marked-up diff output in HTML from the input strings.
     *
     * @deprecated will be removed in TYPO3 v14. Remove together with $stripTags property.
     */
    public function makeDiffDisplay(string $str1, string $str2, DiffGranularity $granularity = DiffGranularity::WORD): string
    {
        trigger_error(__METHOD__ . ' has been marked as deprecated in TYPO3 v13. Use diff() instead.', E_USER_DEPRECATED);
        if ($this->stripTags) {
            $str1 = strip_tags($str1);
            $str2 = strip_tags($str2);
        }
        $granularity = $granularity === DiffGranularity::WORD ? new Word() : new Character();
        $diff = new Diff($granularity);
        return $diff->render($str1, $str2);
    }

    public function diff(string $from, string $to, DiffGranularity $granularity = DiffGranularity::WORD): string
    {
        return (new Diff($granularity === DiffGranularity::WORD ? new Word() : new Character()))->render($from, $to);
    }
}
