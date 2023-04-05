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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller\Fixtures;

final class TypoScriptFrontendControllerTestUserFuncs
{
    /**
     * A USER_INT method referenced in PageWithUserInt.typoscript
     */
    public function userIntCallback(): string
    {
        $GLOBALS['TSFE']->additionalHeaderData[] = 'headerDataFromUserInt';
        $GLOBALS['TSFE']->additionalFooterData[] = 'footerDataFromUserInt';
        return 'userIntContent';
    }

    /**
     * A USER method referenced in PageWithUserObjectUsingSlWithoutLLL.typoscript
     */
    public function slWithoutLLLCallback(): string
    {
        return $GLOBALS['TSFE']->sL('notprefixedWithLLL');
    }

    /**
     * A USER method referenced in PageWithUserObjectUsingSlWithLLL.typoscript
     */
    public function slWithLLLCallback(): string
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page');
    }

    /**
     * A USER_INT method referenced in PageExposingTsfeMpParameter.typoscript
     */
    public function pageExposingMpParameterUserInt(): string
    {
        if ($GLOBALS['TSFE']->MP === '') {
            return 'empty';
        }
        return 'foo' . $GLOBALS['TSFE']->MP . 'bar';
    }
}
