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

namespace TYPO3\CMS\Core\Tests\Unit\ViewHelpers;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\ViewHelpers\IconForRecordViewHelper;
use TYPO3\CMS\Core\ViewHelpers\IconViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Test case
 */
class IconForRecordViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var Icon|ObjectProphecy
     */
    protected $iconProphecy;

    /**
     * @var IconFactory|ObjectProphecy
     */
    protected $iconFactoryProphecy;

    /**
     * @var IconViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->iconProphecy = $this->prophesize(Icon::class);
        $this->iconProphecy->render(Argument::any())->willReturn('icon html');
        $this->iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $this->iconFactoryProphecy->getIconForRecord(Argument::cetera())->willReturn($this->iconProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $this->iconFactoryProphecy->reveal());
    }

    /**
     * @test
     */
    public function renderRendersIconByWayOfTheIconFactoryAccordingToGivenArguments()
    {
        $renderingContextProphecy = $this->prophesize(RenderingContextInterface::class);

        $row = ['uid' => 123];
        $arguments = [
            'table' => 'tt_content',
            'row' => $row,
            'size' => Icon::SIZE_LARGE,
            'alternativeMarkupIdentifier' => 'inline'
        ];
        $iconForRecordViewHelper = new IconForRecordViewHelper();
        $iconForRecordViewHelper->setRenderingContext($renderingContextProphecy->reveal());
        $iconForRecordViewHelper->setArguments($arguments);
        $iconForRecordViewHelper->render();

        $this->iconFactoryProphecy->getIconForRecord('tt_content', $row, Icon::SIZE_LARGE)->shouldHaveBeenCalled();
        $this->iconProphecy->render('inline')->shouldHaveBeenCalled();
    }
}
