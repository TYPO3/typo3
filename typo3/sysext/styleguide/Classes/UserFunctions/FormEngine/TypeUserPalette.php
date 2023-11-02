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

namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

/**
 * A user function rendering a type=user TCA type used in palette_1_1
 *
 * @internal
 */
final class TypeUserPalette
{
    /**
     * @param array $parameters
     * @param object $parentObject
     * @return string
     */
    public function render(array $parameters, $parentObject)
    {
        $html = [];
        $html[] = '
			<div class="t3-form-field-item">
				<input name="data[sys_file_storage][{uid}][is_public]" value="0" type="hidden">
				<input class="checkbox" value="1" name="data[sys_file_storage][{uid}][is_public]_0" type="checkbox" %s>
			</div>';
        return implode(chr(10), $html);
    }
}
