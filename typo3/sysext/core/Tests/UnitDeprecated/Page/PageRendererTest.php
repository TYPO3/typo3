<?php
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Page;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function includingNotAvailableLocalJqueryVersionThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1341505305);

        /** @var PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('availableLocalJqueryVersions', ['1.1.1']);
        $subject->loadJquery('2.2.2');
    }

    /**
     * @test
     */
    public function includingJqueryWithNonAlphnumericNamespaceThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1341571604);

        /** @var PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $subject->loadJquery(null, null, '12sd.12fsd');
        $subject->render();
    }
}
