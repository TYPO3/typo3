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

use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OrderedProviderListTest extends UnitTestCase
{
    /**
     * @test
     */
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

    /**
     * @test
     */
    public function compileReturnsResultChangedByDataProvider(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $formDataProviderMock = $this->createMock(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderMock);
        $providerResult = ['foo'];
        $formDataProviderMock->expects(self::atLeastOnce())->method('addData')->with(self::anything())
            ->willReturn($providerResult);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            FormDataProviderInterface::class => [],
        ]);
        self::assertEquals($providerResult, $subject->compile([]));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
