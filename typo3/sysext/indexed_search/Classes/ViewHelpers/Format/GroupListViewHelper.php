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

namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to iterate an array and returns a formatted representation.
 *
 * ```
 *   <is:format.groupList groups="{data.grList}" />
 * ```
 *
 * @internal
 */
final class GroupListViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('groups', 'array', '', false, []);
    }

    /**
     * Render the given group information as string.
     */
    public function render(): string
    {
        $groups = $this->arguments['groups'];
        $str = [];
        foreach ($groups as $row) {
            $str[] = $row['gr_list'] === '0,-1' ? 'NL' : $row['gr_list'];
        }
        arsort($str);
        return implode('|', $str);
    }
}
