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

use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OnTheFlyTest extends UnitTestCase
{
    protected OnTheFly $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new OnTheFly();
    }

    /**
     * @test
     */
    public function compileThrowsExceptionWithEmptyOnTheFlyList(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1441108674);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileReturnsIncomingData(): void
    {
        $formDataProviderMock = $this->createMock(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderMock);
        $formDataProviderMock->method('addData')->withAnyParameters()->willReturnArgument(0);
        $providerList = [
            FormDataProviderInterface::class,
        ];
        $this->subject->setProviderList($providerList);

        $input = [
            'foo',
        ];

        self::assertEquals($input, $this->subject->compile($input));
    }

    /**
     * @test
     */
    public function compileReturnsResultChangedByDataProvider(): void
    {
        $formDataProviderMock = $this->createMock(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderMock);

        $providerList = [
            FormDataProviderInterface::class,
        ];
        $this->subject->setProviderList($providerList);
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
        $providerList = [
            \stdClass::class,
        ];
        $this->subject->setProviderList($providerList);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1441108719);
        $this->subject->compile([]);
    }
}
