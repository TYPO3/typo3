<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Evaluation;

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

/**
 * Class SourcePath - Used for validation / sanitation of url path segments
 */
class SourcePath
{

    /**
     * JavaScript code for client side validation/evaluation
     *
     * @return string JavaScript code for client side validation/evaluation
     */
    public function returnFieldJS(): string
    {
        return
            'if (value.charAt(0) != "/") { value = "/" + value; };' .
            'if (value.charAt(value.length-1) != "/") { value = value + "/"; };' .
            'value = value.replace(/\/\//g, "/");' .
            'value = value.replace(/ß/g, "ss");' .
            'value = value.replace(/ü/g, "ue");' .
            'value = value.replace(/ä/g, "ae");' .
            'value = value.replace(/ö/g, "oe");' .
            'return value.replace(/[\s*\"\'¢|°\^!?=<>§&$%@{}()[\]]/g, "");';
    }
}
