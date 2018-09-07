<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\ExpressionLanguage;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $typo3 = new \stdClass();
        $typo3->version = TYPO3_version;
        $typo3->branch = TYPO3_branch;
        $typo3->devIpMask = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        $this->expressionLanguageVariables = [
            'applicationContext' => (string)GeneralUtility::getApplicationContext(),
            'typo3' => $typo3,
        ];
    }
}
