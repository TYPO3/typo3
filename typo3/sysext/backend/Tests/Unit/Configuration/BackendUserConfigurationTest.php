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

namespace TYPO3\CMS\Backend\Tests\Unit\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BackendUserConfigurationTest extends UnitTestCase
{
    protected BackendUserConfiguration $backendUserConfiguration;
    protected BackendUserAuthentication&MockObject $backendUserMock;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $this->backendUserConfiguration = new BackendUserConfiguration($this->backendUserMock);
    }

    /**
     * @test
     */
    public function getsConfiguration(): void
    {
        $this->backendUserMock->uc = [
            'key' => 'A',
            'nested' => [
                'key' => 'B',
            ],
        ];

        self::assertEquals('A', $this->backendUserConfiguration->get('key'));
        self::assertEquals('B', $this->backendUserConfiguration->get('nested.key'));
    }

    /**
     * @test
     */
    public function getsAllConfiguration(): void
    {
        $configuration = [
            'foo' => 'A',
            'bar' => 'B',
        ];
        $this->backendUserMock->uc = $configuration;

        self::assertEquals($configuration, $this->backendUserConfiguration->getAll());
    }

    /**
     * @test
     */
    public function setsConfiguration(): void
    {
        $this->backendUserMock->uc = [
            'foo' => 'A',
        ];

        $this->backendUserMock->expects(self::atLeastOnce())->method('writeUC');

        $this->backendUserConfiguration->set('foo', 'X');
        $this->backendUserConfiguration->set('bar', 'Y');
        $this->backendUserConfiguration->set('nested.bar', 'Z');

        $expected = [
            'foo' => 'X',
            'bar' => 'Y',
            'nested' => [
                'bar' => 'Z',
            ],
        ];
        self::assertEquals($expected, $this->backendUserMock->uc);
    }

    /**
     * @test
     */
    public function addsToListConfigurationOption(): void
    {
        $this->backendUserMock->uc = [
            'foo' => 'A',
            'nested' => [
                'foo' => '',
            ],
        ];

        $this->backendUserMock->expects(self::atLeastOnce())->method('writeUC');

        $this->backendUserConfiguration->addToList('foo', 'X');
        $this->backendUserConfiguration->addToList('nested.foo', 'X');
        $this->backendUserConfiguration->addToList('nested.foo', 'Z');
        $this->backendUserConfiguration->addToList('nested.foo', 'Z');
        $expected = [
            'foo' => 'A,X',
            'nested' => [
                'foo' => ',X,Z',
            ],
        ];
        self::assertEquals($expected, $this->backendUserMock->uc);
    }

    /**
     * @test
     */
    public function removesFromListConfigurationOption(): void
    {
        $this->backendUserMock->uc = [
            'foo' => 'A,B',
            'nested' => [
                'foo' => 'A,B,C',
            ],
        ];

        $this->backendUserMock->expects(self::atLeastOnce())->method('writeUC');

        $this->backendUserConfiguration->removeFromList('foo', 'B');
        $this->backendUserConfiguration->removeFromList('nested.foo', 'B');

        $expected = [
            'foo' => 'A',
            'nested' => [
                'foo' => 'A,C',
            ],
        ];
        self::assertEquals($expected, $this->backendUserMock->uc);
    }

    /**
     * @test
     */
    public function clearsConfiguration(): void
    {
        $this->backendUserMock->expects(self::atLeastOnce())->method('resetUC');
        $this->backendUserConfiguration->clear();
    }

    /**
     * @test
     */
    public function unsetsConfigurationOption(): void
    {
        $this->backendUserMock->uc = [
            'foo' => 'A',
            'bar' => 'B',
        ];

        $this->backendUserMock->expects(self::atLeastOnce())->method('writeUC');

        $this->backendUserConfiguration->unsetOption('foo');
        $this->backendUserConfiguration->unsetOption('foo');

        $expected = [
            'bar' => 'B',
        ];
        self::assertEquals($expected, $this->backendUserMock->uc);
    }
}
