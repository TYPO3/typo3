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

namespace TYPO3\CMS\Reports\Tests\Unit\Report\Status;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactoryInterface;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\Report\Status\Status;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as StatusValue;
use TYPO3\CMS\Reports\StatusProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StatusTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
    }

    /**
     * @test
     */
    public function getSystemStatusCollectsAllStatusProvider(): void
    {
        $statusProviderArguments = [
            [
                [
                    new StatusValue('Status 1', 'Value 1', 'Message 1'),
                ],
                'StatusProvider 1',
                false,
            ],
            [
                [
                    new StatusValue('Status 1', 'Value 1', 'Message 1'),
                    new StatusValue('Status 2', 'Value 2', 'Message 2'),
                ],
                'StatusProvider 2',
                false,
            ],
            [
                [],
                'StatusProvider 3',
                true,
            ],
        ];

        $subject = $this->createSubject($statusProviderArguments);
        $request = new ServerRequest();
        $httpHeader = ['Test-Header', 'test'];
        $request = $request->withHeader(...$httpHeader);
        $result = $subject->getSystemStatus($request);

        self::assertCount(count($statusProviderArguments), $result);
        self::assertArrayHasKey($statusProviderArguments[0][1], $result);
        self::assertArrayHasKey($statusProviderArguments[1][1], $result);
        self::assertArrayHasKey($statusProviderArguments[2][1], $result);
        self::assertSame($statusProviderArguments[0][0], $result[$statusProviderArguments[0][1]]);
        self::assertSame($statusProviderArguments[1][0], $result[$statusProviderArguments[1][1]]);
        self::assertSame($httpHeader[0], $result[$statusProviderArguments[2][1]][0]->getTitle());
        self::assertSame($httpHeader[1], $result[$statusProviderArguments[2][1]][0]->getValue());
    }

    /**
     * @test
     */
    public function getDetailedSystemStatusReturnsOnlyExtendedStatuses(): void
    {
        $statusProviderArguments = [
            [
                [
                    new StatusValue('Status 1', 'Value 1', 'Message 1'),
                ],
                'StatusProvider 1',
            ],
            [
                [
                    new StatusValue('Status 1', 'Value 1', 'Message 1'),
                    new StatusValue('Status 2', 'Value 2', 'Message 2'),
                ],
                'StatusProvider 2',
            ],
            [
                [
                    new StatusValue('Status 1', 'Value 1', 'Message 1'),
                ],
                'StatusProvider 3',
                false,
                true,
            ],
        ];

        $subject = $this->createSubject($statusProviderArguments);
        $result = $subject->getDetailedSystemStatus();

        self::assertCount(1, $result);
        self::assertSame($statusProviderArguments[2][0], $result[$statusProviderArguments[2][1]]);
    }

    /**
     * @param array<array{0: StatusValue[], 1: string, 2?: bool, 3?: bool}> $statusProviderArguments
     */
    private function createSubject(array $statusProviderArguments): Status
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $cacheManager = $this->prophesize(CacheManager::class)->reveal();
        $viewHelperResolverFactory = $this->prophesize(ViewHelperResolverFactoryInterface::class)->reveal();
        $renderingContextFactory = new RenderingContextFactory($container, $cacheManager, $viewHelperResolverFactory);
        $packageManager = $this->prophesize(PackageManager::class)->reveal();
        $backendViewFactory = new BackendViewFactory($renderingContextFactory, $packageManager);
        $statusRegistry = new StatusRegistry($this->generateStatusProviders($statusProviderArguments));

        return new Status($backendViewFactory, $statusRegistry);
    }

    /**
     * @param array<array{0: StatusValue[], 1: string, 2?: bool, 3?: bool}> $statusProviderArgumentsList
     */
    private function generateStatusProviders(array $statusProviderArgumentsList): \Generator
    {
        foreach ($statusProviderArgumentsList as $statusProviderArguments) {
            yield $this->createStatusProvider(...$statusProviderArguments);
        }
    }

    /**
     * @param StatusValue[] $statuses
     */
    private function createStatusProvider(array $statuses, string $label, bool $requestAware = false, bool $extended = false): StatusProviderInterface
    {
        if ($requestAware) {
            return new class($label) implements RequestAwareStatusProviderInterface {
                public function __construct(
                    private readonly string $label,
                ) {
                }

                public function getStatus(ServerRequestInterface $request = null): array
                {
                    $statuses = [];
                    foreach ($request->getHeaders() as $key => $header) {
                        $statuses[] = new StatusValue($key, $header[0], 'status from request');
                    }
                    return $statuses;
                }

                public function getLabel(): string
                {
                    return $this->label;
                }
            };
        }

        if ($extended) {
            return new class($statuses, $label) implements StatusProviderInterface, ExtendedStatusProviderInterface {
                public function __construct(
                    private readonly array $statuses,
                    private readonly string $label,
                ) {
                }

                public function getStatus(): array
                {
                    return $this->statuses;
                }

                public function getLabel(): string
                {
                    return $this->label;
                }

                public function getDetailedStatus(): array
                {
                    return $this->statuses;
                }
            };
        }

        return new class($statuses, $label) implements StatusProviderInterface {
            public function __construct(
                private readonly array $statuses,
                private readonly string $label,
            ) {
            }

            public function getStatus(): array
            {
                return $this->statuses;
            }

            public function getLabel(): string
            {
                return $this->label;
            }
        };
    }
}
