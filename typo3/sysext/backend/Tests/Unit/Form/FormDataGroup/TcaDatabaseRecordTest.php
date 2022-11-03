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

use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaDatabaseRecordTest extends UnitTestCase
{
    protected TcaDatabaseRecord $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaDatabaseRecord();
    }

    /**
     * @test
     */
    public function compileReturnsIncomingData(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'] = [];

        $input = ['foo'];

        self::assertEquals($input, $this->subject->compile($input));
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
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'] = [
            FormDataProviderInterface::class => [],
        ];
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderMock);
        $providerResult = ['foo'];
        $formDataProviderMock->expects(self::atLeastOnce())->method('addData')->with(self::anything())
            ->willReturn($providerResult);

        self::assertEquals($providerResult, $this->subject->compile([]));
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface(): void
    {
        $orderingServiceMock = $this->createMock(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceMock);
        $orderingServiceMock->method('orderByDependencies')->withAnyParameters()->willReturnArgument(0);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'] = [
            \stdClass::class => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1485299408);

        $this->subject->compile([]);
    }
}
