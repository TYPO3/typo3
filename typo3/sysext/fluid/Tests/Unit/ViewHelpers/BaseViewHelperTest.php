<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Fluid\ViewHelpers\BaseViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

class BaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderTakesBaseUriFromServerRequest()
    {
        $baseUri = 'http://typo3.org/';

        /** @var NormalizedParams|ObjectProphecy $normalizedParams */
        $normalizedParams = $this->prophesize(NormalizedParams::class);
        $normalizedParams->getSiteUrl()->willReturn($baseUri);
        /** @var ServerRequestInterface|ObjectProphecy $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request->getAttribute('normalizedParams')->willReturn($normalizedParams->reveal());
        $GLOBALS['TYPO3_REQUEST'] = $request->reveal();

        $viewHelper = new BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);

        self::assertSame('<base href="' . $baseUri . '" />', $viewHelper->render());
    }
}
