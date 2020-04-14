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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap;

/**
 * Test case for frontend requests having site handling configured
 */
class LinkHandlingController
{
    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * @return string
     */
    public function mainAction(): string
    {
        $instruction = RequestBootstrap::getInternalRequest()
            ->getInstruction(LinkHandlingController::class);
        if (!$instruction instanceof ArrayValueInstruction) {
            return '';
        }
        return $this->cObj->cObjGet($instruction->getArray());
    }

    /**
     * @return string
     */
    public function dumpPageArgumentsAction(): string
    {
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        /** @var SiteLanguage $language */
        $language = $request->getAttribute('language');
        return json_encode([
            'pageId' => $pageArguments->getPageId(),
            'pageType' => $pageArguments->getPageType(),
            'languageId' => $language->getLanguageId(),
            'dynamicArguments' => $pageArguments->getDynamicArguments(),
            'staticArguments' => $pageArguments->getStaticArguments(),
            'queryArguments' => $pageArguments->getQueryArguments(),
            'requestQueryParams' => $request->getQueryParams(),
            '_GET' => $_GET,
        ]);
    }
}
