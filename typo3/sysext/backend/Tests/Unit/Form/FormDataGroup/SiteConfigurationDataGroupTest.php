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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataGroup;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteConfigurationDataGroupTest extends UnitTestCase
{
    protected SiteConfigurationDataGroup $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SiteConfigurationDataGroup();
    }

    #[Test]
    public function compileReturnsIncomingData(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['siteConfiguration'] = [];

        $input = ['foo'];

        self::assertEquals($input, $this->subject->compile($input));
    }

    #[Test]
    public function compileReturnsResultChangedByDataProvider(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['siteConfiguration'] = [
            \stdClass::class => [],
        ];
        GeneralUtility::addInstance(
            \stdClass::class,
            new class () extends \stdClass implements FormDataProviderInterface {
                public function addData(array $result)
                {
                    return ['foo'];
                }
            }
        );

        self::assertEquals(['foo'], $this->subject->compile([]));
    }

    #[Test]
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['siteConfiguration'] = [
            \stdClass::class => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1485299408);

        $this->subject->compile([]);
    }
}
