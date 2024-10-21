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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class IconForRecordViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderRendersIconCallingIconFactoryAccordingToGivenArguments(): void
    {
        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->willReturn('icon html');
        $iconMock->expects(self::atLeastOnce())->method('render')->with('inline');
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIconForRecord')->with(self::anything())->willReturn($iconMock);
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIconForRecord')
            ->with('tt_content', ['uid' => 123], IconSize::LARGE);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<core:iconForRecord table="tt_content" row="{uid: 123}" size="large" alternativeMarkupIdentifier="inline" />'
        );
        (new TemplateView($context))->render();
    }
}
