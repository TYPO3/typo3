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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc;

use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ResponseTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Response|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function propertyContentInitiallyIsNull(): void
    {
        self::assertNull($this->response->getContent());
    }

    /**
     * @test
     */
    public function setContentSetsContentCorrectly(): void
    {
        $this->response->setContent('foo');
        self::assertSame('foo', $this->response->getContent());
    }

    /**
     * @test
     */
    public function appendContentAppendsContentCorrectly(): void
    {
        $this->response->setContent('foo');
        $this->response->appendContent('bar');
        self::assertSame('foobar', $this->response->getContent());
    }

    /**
     * @test
     */
    public function getContentReturnsContentCorrectly(): void
    {
        $this->response->setContent('foo');
        self::assertSame('foo', $this->response->getContent());
    }

    /**
     * @test
     */
    public function __toStringReturnsActualContent(): void
    {
        $this->response->setContent('foo');
        self::assertSame('foo', (string)$this->response);
    }
}
