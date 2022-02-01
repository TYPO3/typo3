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

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for TYPO3\CMS\Backend\Configuration\BackendUserConfiguration
 */
class BackendUserConfigurationTest extends UnitTestCase
{
    use ProphecyTrait;

    protected BackendUserConfiguration $backendUserConfiguration;

    /** @var ObjectProphecy<BackendUserAuthentication> */
    protected ObjectProphecy $backendUserProphecy;

    protected BackendUserAuthentication $backendUser;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        $this->backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $this->backendUser = $this->backendUserProphecy->reveal();
        $this->backendUserConfiguration = new BackendUserConfiguration($this->backendUser);
    }

    /**
     * @test
     */
    public function getsConfiguration(): void
    {
        $this->backendUser->uc = [
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
        $this->backendUser->uc = $configuration;

        self::assertEquals($configuration, $this->backendUserConfiguration->getAll());
    }

    /**
     * @test
     */
    public function setsConfiguration(): void
    {
        $this->backendUser->uc = [
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

        $this->backendUserProphecy->writeUC()->shouldHaveBeenCalled();
        self::assertEquals($expected, $this->backendUser->uc);
    }

    /**
     * @test
     */
    public function addsToListConfigurationOption(): void
    {
        $this->backendUser->uc = [
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
                'foo' => ',X,Z',
            ],
        ];
        $this->backendUserProphecy->writeUC()->shouldHaveBeenCalled();
        self::assertEquals($expected, $this->backendUser->uc);
    }

    /**
     * @test
     */
    public function removesFromListConfigurationOption(): void
    {
        $this->backendUser->uc = [
            'foo' => 'A,B',
            'nested' => [
                'foo' => 'A,B,C',
            ],
        ];

        $this->backendUserConfiguration->removeFromList('foo', 'B');
        $this->backendUserConfiguration->removeFromList('nested.foo', 'B');

        $expected = [
            'foo' => 'A',
            'nested' => [
                'foo' => 'A,C',
            ],
        ];
        $this->backendUserProphecy->writeUC()->shouldHaveBeenCalled();
        self::assertEquals($expected, $this->backendUser->uc);
    }

    /**
     * @test
     */
    public function clearsConfiguration(): void
    {
        $this->backendUserConfiguration->clear();
        $this->backendUserProphecy->resetUC()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function unsetsConfigurationOption(): void
    {
        $this->backendUser->uc = [
            'foo' => 'A',
            'bar' => 'B',
        ];

        $this->backendUserConfiguration->unsetOption('foo');
        $this->backendUserConfiguration->unsetOption('foo');

        $expected = [
            'bar' => 'B',
        ];
        $this->backendUserProphecy->writeUC()->shouldHaveBeenCalled();
        self::assertEquals($expected, $this->backendUser->uc);
    }
}
