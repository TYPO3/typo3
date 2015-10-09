<?php
namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

/**
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
 * A user function rendering a type=user TCA type used in palette_1_1
 */
class TypeUserPalette
{
    /**
     * @param array $parameters
     * @param $parentObject
     * @return string
     */
    public function render(array $parameters, $parentObject)
    {
        $html = array();
        $html[] = '
			<div class="t3-form-field-item">
				<input name="data[sys_file_storage][{uid}][is_public]" value="0" type="hidden">
				<input class="checkbox" value="1" name="data[sys_file_storage][{uid}][is_public]_0" type="checkbox" %s>
			</div>';
        return implode(LF, $html);
    }
}
