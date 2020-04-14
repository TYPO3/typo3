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

use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for TYPO3\CMS\Backend\Configuration\BackendUserConfiguration
 */
class BackendUserConfigurationTest extends UnitTestCase
{
    /**
     * @var BackendUserConfiguration
     */
    protected $backendUserConfiguration;

    /**
     * @var BackendUserAuthentication|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $backendUser;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        /** @var BackendUserAuthentication|\Prophecy\Prophecy\ObjectProphecy */
        $this->backendUser = $this->prophesize(BackendUserAuthentication::class);
        $this->backendUserConfiguration = new BackendUserConfiguration($this->backendUser->reveal());
    }

    /**
     * @test
     */
    public function getsConfiguration()
    {
        $this->backendUser->reveal()->uc = [
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
    public function getsAllConfiguration()
    {
        $configuration = [
            'foo' => 'A',
            'bar' => 'B',
        ];
        $this->backendUser->reveal()->uc = $configuration;

        self::assertEquals($configuration, $this->backendUserConfiguration->getAll());
    }

    /**
     * @test
     */
    public function setsConfiguration()
    {
        $this->backendUser->reveal()->uc = [
            'foo' => 'A',
        ];

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

        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function addsToListConfigurationOption()
    {
        $this->backendUser->reveal()->uc = [
            'foo' => 'A',
            'nested' => [
                'foo' => '',
            ],
        ];

        $this->backendUserConfiguration->addToList('foo', 'X');
        $this->backendUserConfiguration->addToList('nested.foo', 'X');
        $this->backendUserConfiguration->addToList('nested.foo', 'Z');
        $this->backendUserConfiguration->addToList('nested.foo', 'Z');

        $expected = [
            'foo' => 'A,X',
            'nested' => [
                'foo' => '',
            ],
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
        $expected = [
            'foo' => 'A,X',
            'nested' => [
                'foo' => ',X',
            ],
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
        $expected = [
            'foo' => 'A,X',
            'nested' => [
                'foo' => ',X,Z',
            ],
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function removesFromListConfigurationOption()
    {
        $this->backendUser->reveal()->uc = [
            'foo' => 'A,B',
            'nested' => [
                'foo' => 'A,B,C',
            ],
        ];

        $this->backendUserConfiguration->removeFromList('foo', 'B');
        $this->backendUserConfiguration->removeFromList('nested.foo', 'B');
        $this->backendUserConfiguration->removeFromList('nested.foo', 'B');

        $expected = [
            'foo' => 'A',
            'nested' => [
                'foo' => 'A,B,C',
            ],
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
        $expected = [
            'foo' => 'A',
            'nested' => [
                'foo' => 'A,C',
            ],
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function clearsConfiguration()
    {
        $this->backendUserConfiguration->clear();

        $this->backendUser->resetUC()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function unsetsConfigurationOption()
    {
        $this->backendUser->reveal()->uc = [
            'foo' => 'A',
            'bar' => 'B',
        ];

        $this->backendUserConfiguration->unsetOption('foo');
        $this->backendUserConfiguration->unsetOption('foo');

        $expected = [
            'bar' => 'B',
        ];
        $this->backendUser->writeUC($expected)->shouldHaveBeenCalled();
    }
}
