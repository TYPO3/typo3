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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Web\Routing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UriBuilderTest extends FunctionalTestCase
{
    #[Test]
    public function buildTypolinkConfigurationUsesCurrentPageUidIfTargetPageUidIsNotSet(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(123);
        $request = (new ServerRequest())
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($request));
        $currentContentObject = $this->get(ContentObjectRenderer::class);
        $currentContentObject->setRequest($request);
        $request = $request->withAttribute('currentContentObject', $currentContentObject);
        $expectedConfiguration = ['parameter' => 123];
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }
}
