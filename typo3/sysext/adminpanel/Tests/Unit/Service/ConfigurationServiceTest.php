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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Service;

use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\MainModuleFixture;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\SubModuleFixture;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $beUserProphecy;

    public function setUp(): void
    {
        parent::setUp();
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();
    }

    /**
     * @test
     */
    public function getMainConfigurationReturnsTsConfigFromUser(): void
    {
        $userTsAdmPanelConfig = [
            'enable.' => [
                'all' => '1',
            ],
        ];
        $this->setUpUserTsConfigForAdmPanel($userTsAdmPanelConfig);

        $configurationService = new ConfigurationService();
        $result = $configurationService->getMainConfiguration();

        self::assertSame($userTsAdmPanelConfig, $result);
    }

    /**
     * @test
     */
    public function getConfigurationOptionReturnsEmptyStringIfNoConfigurationFound(): void
    {
        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('foo', 'bar');
        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function getConfigurationOptionReturnsOverrideOptionIfSet(): void
    {
        $this->setUpUserTsConfigForAdmPanel(
            [
                'override.' => [
                    'preview.' => [
                        'showHiddenPages' => '1',
                    ],
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    /**
     * @test
     */
    public function getConfigurationOptionCastsResultToString(): void
    {
        $this->setUpUserTsConfigForAdmPanel(
            [
                'override.' => [
                    'preview.' => [
                        'showHiddenPages' => 1,
                    ],
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    public function getConfigurationOptionEmptyArgumentDataProvider(): array
    {
        return [
            'empty identifier' => [
                '',
                'foo',
            ],
            'empty option' => [
                'foo',
                '',
            ],
            'both empty' => [
                '',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getConfigurationOptionEmptyArgumentDataProvider
     * @param $identifier
     * @param $option
     */
    public function getConfigurationOptionThrowsExceptionOnEmptyArgument($identifier, $option): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1532861423);

        $configurationService = new ConfigurationService();
        $configurationService->getConfigurationOption($identifier, $option);
    }

    /**
     * @test
     */
    public function getConfigurationOptionReturnsSettingFromUcIfNoOverrideGiven(): void
    {
        $this->setUpUserTsConfigForAdmPanel([]);
        $this->beUserProphecy->uc = [
            'AdminPanel' => [
                'preview_showHiddenPages' => '1',
            ],
        ];

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    /**
     * @test
     */
    public function saveConfigurationTriggersOnSubmitOnEnabledModules(): void
    {
        $subModuleFixture = $this->prophesize(SubModuleFixture::class);
        $mainModuleFixture = $this->prophesize(MainModuleFixture::class);
        $mainModuleFixture->isEnabled()->willReturn(true);
        $mainModuleFixture->onSubmit(Argument::cetera())->shouldBeCalled()->hasReturnVoid();
        $mainModuleFixture->getSubModules()->willReturn(
            [$subModuleFixture->reveal()]
        );
        $modules = [
            $mainModuleFixture->reveal(),
        ];

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $configurationService = new ConfigurationService();
        $configurationService->saveConfiguration($modules, $requestProphecy->reveal());

        $mainModuleFixture->onSubmit([], $requestProphecy->reveal())->shouldHaveBeenCalled();
        $subModuleFixture->onSubmit([], $requestProphecy->reveal())->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function saveConfigurationSavesMergedExistingAndNewConfiguration(): void
    {
        // existing configuration from UC
        $this->beUserProphecy->uc = [
            'AdminPanel' => [
                'foo' => 'bar',
            ],
        ];

        // new configuration to save
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->willReturn(
            [
                'TSFE_ADMIN_PANEL' => [
                    'baz' => 'bam',
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $configurationService->saveConfiguration([], $requestProphecy->reveal());

        $expected = [
            'AdminPanel' => [
                'foo' => 'bar',
                'baz' => 'bam',
            ],
        ];
        self::assertSame($expected, $this->beUserProphecy->uc);
        $this->beUserProphecy->writeUC()->shouldHaveBeenCalled();
    }

    /**
     * @param $userTsAdmPanelConfig
     */
    private function setUpUserTsConfigForAdmPanel($userTsAdmPanelConfig): void
    {
        $this->beUserProphecy->getTSConfig()->willReturn(
            ['admPanel.' => $userTsAdmPanelConfig]
        );
    }
}
