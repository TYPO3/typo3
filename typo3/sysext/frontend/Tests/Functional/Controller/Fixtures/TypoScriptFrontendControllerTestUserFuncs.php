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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class TypoScriptFrontendControllerTestUserFuncs
{
    /**
     * A USER_INT method referenced in PageWithUserInt.typoscript
     */
    public function userIntCallback(): string
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addHeaderData('headerDataFromUserInt');
        $pageRenderer->addFooterData('footerDataFromUserInt');
        return 'userIntContent';
    }

    /**
     * A USER method referenced in PageWithUserObjectUsingSlWithoutLLL.typoscript
     */
    public function slWithoutLLLCallback($_, $__, ServerRequestInterface $request): string
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromSiteLanguage($request->getAttribute('language'))
            ->sL('notprefixedWithLLL');
    }

    /**
     * A USER method referenced in PageWithUserObjectUsingSlWithLLL.typoscript
     */
    public function slWithLLLCallback($_, $__, ServerRequestInterface $request): string
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromSiteLanguage($request->getAttribute('language'))
            ->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page');
    }
}
