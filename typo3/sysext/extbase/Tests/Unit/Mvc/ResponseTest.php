<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc;

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

/**
 * Test case
 */
class ResponseTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Response|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $mockResponse;

    protected function setUp()
    {
        $this->mockResponse = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Response::class, ['dummy']);
    }

    /**
     * @test
     */
    public function propertyContentInitiallyIsNull()
    {
        $this->assertNull($this->mockResponse->_get('content'));
    }

    /**
     * @test
     */
    public function setContentSetsContentCorrectly()
    {
        $this->mockResponse->setContent('foo');
        $this->assertSame('foo', $this->mockResponse->_get('content'));
    }

    /**
     * @test
     */
    public function appendContentAppendsContentCorrectly()
    {
        $this->mockResponse->_set('content', 'foo');
        $this->mockResponse->appendContent('bar');
        $this->assertSame('foobar', $this->mockResponse->_get('content'));
    }

    /**
     * @test
     */
    public function getContentReturnsContentCorrectly()
    {
        $this->mockResponse->_set('content', 'foo');
        $this->assertSame('foo', $this->mockResponse->getContent());
    }

    /**
     * @test
     */
    public function __toStringReturnsActualContent()
    {
        $this->mockResponse->_set('content', 'foo');
        $this->assertSame('foo', (string)$this->mockResponse);
    }
}
