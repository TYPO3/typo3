<?php

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class OnTheFlyTest extends UnitTestCase
{
    /**
     * @var OnTheFly
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new OnTheFly();
    }

    /**
     * @test
     */
    public function compileThrowsExceptionWithEmptyOnTheFlyList()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1441108674);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileReturnsIncomingData()
    {
        /** @var FormDataProviderInterface|ObjectProphecy $formDataProviderProphecy */
        $formDataProviderProphecy = $this->prophesize(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderProphecy->reveal());
        $formDataProviderProphecy->addData(Argument::cetera())->willReturnArgument(0);
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
    public function compileReturnsResultChangedByDataProvider()
    {
        /** @var FormDataProviderInterface|ObjectProphecy $formDataProviderProphecy */
        $formDataProviderProphecy = $this->prophesize(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderProphecy->reveal());

        $providerList = [
            FormDataProviderInterface::class,
        ];
        $this->subject->setProviderList($providerList);
        $providerResult = ['foo'];
        $formDataProviderProphecy->addData(Argument::cetera())->shouldBeCalled()->willReturn($providerResult);

        self::assertEquals($providerResult, $this->subject->compile([]));
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface()
    {
        /** @var FormDataProviderInterface|ObjectProphecy $formDataProviderProphecy */
        $formDataProviderProphecy = $this->prophesize(\stdClass::class);
        GeneralUtility::addInstance(\stdClass::class, $formDataProviderProphecy->reveal());
        $providerList = [
            \stdClass::class,
        ];
        $this->subject->setProviderList($providerList);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1441108719);
        $this->subject->compile([]);
    }
}
