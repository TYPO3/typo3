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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

use TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder;
use TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetContextNotFoundException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AjaxWidgetContextHolderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getThrowsExceptionIfWidgetContextIsNotFound()
    {
        /** @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder */
        $ajaxWidgetContextHolder = $this->getMockBuilder(AjaxWidgetContextHolder::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(WidgetContextNotFoundException::class);
        $this->expectExceptionCode(1284793775);

        $ajaxWidgetContextHolder->get(42);
    }
}
