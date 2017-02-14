<?php
namespace TYPO3\CMS\Extbase\Hook\DataHandler;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class CheckFlexFormValue
{
    /**
     * Check flexform value before merge
     *
     * @param DataHandler $dataHander
     * @param array &$currentValue
     * @param array &$newValue
     */
    public function checkFlexFormValue_beforeMerge(DataHandler $dataHander, array &$currentValue, array &$newValue)
    {
        $currentValue = $this->removeSwitchableControllerActionsRecursive($currentValue);
    }

    /**
     * Remove switchable controller actions recursively
     *
     * @param array $a
     * @return array
     */
    protected function removeSwitchableControllerActionsRecursive(array $a)
    {
        $r = [];

        foreach ($a as $k => $v) {
            if ($k === 'switchableControllerActions') {
                continue;
            }

            $r[$k] = is_array($v) ? $this->removeSwitchableControllerActionsRecursive($v) : $v;
        }

        return $r;
    }
}
