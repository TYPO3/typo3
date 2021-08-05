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

namespace TYPO3\CMS\Core\Tests\Functional\ViewHelpers;

use Prophecy\Argument;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IconForRecordViewHelperTest extends FunctionalTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function renderRendersIconCallingIconFactoryAccordingToGivenArguments(): void
    {
        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render(Argument::any())->willReturn('icon html');
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $iconFactoryProphecy->getIconForRecord(Argument::cetera())->willReturn($iconProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $view = new StandaloneView();
        $view->setTemplateSource('<core:iconForRecord table="tt_content" row="{uid: 123}" size="large" alternativeMarkupIdentifier="inline" />');
        $view->render();

        $iconFactoryProphecy->getIconForRecord('tt_content', ['uid' => 123], Icon::SIZE_LARGE)->shouldHaveBeenCalled();
        $iconProphecy->render('inline')->shouldHaveBeenCalled();
    }
}
