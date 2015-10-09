<?php
namespace TYPO3\CMS\Styleguide\UserFunctions\ExtensionConfiguration;

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
 * User function 1
 */
class User1
{
    /**
     * Simple user function returning a var_dump of input parameters
     *
     * @param array $params
     * @param mixed $tsObj
     * @return string
     */
    public function user_1(&$params, &$tsObj)
    {
        $out = '';

        // Params;
        $out .= '<pre>';
        ob_start();
        var_dump($params);
        $out .= ob_get_contents();
        ob_end_clean();
        $out .= '</pre>';

        return $out;
    }
}
