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
 * A user function used in radio_5
 */
class TypeRadio5ItemsProcFunc
{
    /**
     * Add two items to existing ones
     *
     * @param $params
     */
    public function itemsProcFunc(&$params)
    {
        $params['items'][] = array('item 1 from itemProcFunc()', 3, null);
        $params['items'][] = array('item 2 from itemProcFunc()', 4, null);
    }
}
