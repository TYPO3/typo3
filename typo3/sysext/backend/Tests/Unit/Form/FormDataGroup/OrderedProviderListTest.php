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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class OrderedProviderListTest extends UnitTestCase
{
    /**
     * @test
     */
    public function compileReturnsIncomingData()
    {
        /** @var DependencyOrderingService|ObjectProphecy $orderingServiceProphecy */
        $orderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceProphecy->reveal());
        $orderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);

        $input = ['foo'];

        $subject = new OrderedProviderList();
        $subject->setProviderList([]);
        self::assertEquals($input, $subject->compile($input));
    }

    /**
     * @test
     */
    public function compileReturnsResultChangedByDataProvider()
    {
        /** @var DependencyOrderingService|ObjectProphecy $orderingServiceProphecy */
        $orderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceProphecy->reveal());
        $orderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);

        /** @var FormDataProviderInterface|ObjectProphecy $formDataProviderProphecy */
        $formDataProviderProphecy = $this->prophesize(FormDataProviderInterface::class);
        GeneralUtility::addInstance(FormDataProviderInterface::class, $formDataProviderProphecy->reveal());
        $providerResult = ['foo'];
        $formDataProviderProphecy->addData(Argument::cetera())->shouldBeCalled()->willReturn($providerResult);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            FormDataProviderInterface::class => [],
        ]);
        self::assertEquals($providerResult, $subject->compile([]));
    }

    /**
     * @test
     */
    public function compileDoesNotCallDisabledDataProvider()
    {
        /** @var DependencyOrderingService|ObjectProphecy $orderingServiceProphecy */
        $orderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceProphecy->reveal());
        $orderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);

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
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface()
    {
        /** @var DependencyOrderingService|ObjectProphecy $orderingServiceProphecy */
        $orderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        GeneralUtility::addInstance(DependencyOrderingService::class, $orderingServiceProphecy->reveal());
        $orderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);

        /** @var FormDataProviderInterface|ObjectProphecy $formDataProviderProphecy */
        $formDataProviderProphecy = $this->prophesize(\stdClass::class);
        GeneralUtility::addInstance(\stdClass::class, $formDataProviderProphecy->reveal());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1485299408);

        $subject = new OrderedProviderList();
        $subject->setProviderList([
            \stdClass::class => [],
        ]);
        $subject->compile([]);
    }
}
