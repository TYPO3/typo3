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
use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OrderedProviderListTest extends UnitTestCase
{
    #[Test]
    public function compileReturnsIncomingData(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $input = ['foo'];

        $subject = new OrderedProviderList();
        $subject->setProviderList([]);
        self::assertEquals($input, $subject->compile($input));
    }

    #[Test]
    public function compileReturnsResultChangedByDataProvider(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $formDataProvider = new class () extends \stdClass implements FormDataProviderInterface {
            public function addData(array $result)
            {
                return ['foo'];
            }
        };

        GeneralUtility::addInstance(\stdClass::class, $formDataProvider);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            \stdClass::class => [],
        ]);
        self::assertEquals(['foo'], $subject->compile([]));
    }

    #[Test]
    public function compileDoesNotCallDisabledDataProvider(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            FormDataProviderInterface::class => [
                'disabled' => true,
            ],
        ]);
        $input = ['foo'];
        self::assertEquals($input, $subject->compile($input));
    }

    #[Test]
    public function compileThrowsExceptionIfImplementationClassDoesNotExist(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1685542507);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            '\\Invalid\\Class\\String' => [],
        ]);
        $subject->compile([]);
    }

    #[Test]
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1485299408);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            \stdClass::class => [],
        ]);
        $subject->compile([]);
    }
}
