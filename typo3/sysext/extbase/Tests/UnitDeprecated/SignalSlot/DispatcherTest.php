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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\SignalSlot;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\SignalSlot\Fixtures\OnlyClassNameSpecifiedFixture;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\SignalSlot\Fixtures\SlotMethodDoesNotExistFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DispatcherTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ObjectManagerInterface|ObjectProphecy
     */
    protected $objectManagerProphecy;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $signalSlotDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManagerProphecy = $this->prophesize(ObjectManagerInterface::class);

        $this->signalSlotDispatcher = new Dispatcher(
            $this->objectManagerProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );
    }

    /**
     * @test
     */
    public function connectAllowsForConnectingASlotWithASignal(): void
    {
        $mockSignal = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['emitSomeSignal'])
            ->getMock();
        $mockSlot = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['someSlotMethod'])
            ->getMock();
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', true);
        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => true],
        ];
        self::assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsObjectsInPlaceOfTheClassName(): void
    {
        $mockSignal = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['emitSomeSignal'])
            ->getMock();
        $mockSlot = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['someSlotMethod'])
            ->getMock();
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod', true);
        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => true],
        ];
        self::assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsClosuresActingAsASlot(): void
    {
        $mockSignal = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['emitSomeSignal'])
            ->getMock();
        $mockSlot = static function () {
        };
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo', true);
        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => true],
        ];
        self::assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheSlotMethod(): void
    {
        $arguments = [];
        $mockSlot = static function () use (&$arguments) {
            $arguments = func_get_args();
        };
        $this->signalSlotDispatcher->connect('Foo', 'bar', $mockSlot, '', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'bar', ['bar', 'quux']);
        self::assertSame(['bar', 'quux'], $arguments);
    }

    /**
     * @test
     */
    public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified(): void
    {
        $slotClassName = OnlyClassNameSpecifiedFixture::class;
        $mockSlot = new OnlyClassNameSpecifiedFixture();
        $this->objectManagerProphecy->get($slotClassName)->willReturn($mockSlot);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
        self::assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverArgumentsReturnedByAFormerSlot(): void
    {
        $firstMockSlot = $this->createMock(SlotFixture::class);
        $firstMockSlot->expects(self::once())
            ->method('slot')
            ->willReturnCallback(
                static function ($foo, $baz) {
                    return ['modified_' . $foo, 'modified_' . $baz];
                }
            );

        $secondMockSlot = $this->createMock(SlotFixture::class);
        $secondMockSlot->expects(self::once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot', false);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot', false);

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverArgumentsReturnedByAFormerSlotWithoutInterferingWithSignalSlotInformation(): void
    {
        $firstMockSlot = $this->createMock(SlotFixture::class);
        $firstMockSlot->expects(self::once())
            ->method('slot')
            ->willReturnCallback(
                static function ($foo, $baz) {
                    return ['modified_' . $foo, 'modified_' . $baz];
                }
            );

        $secondMockSlot = $this->createMock(SlotFixture::class);
        $secondMockSlot->expects(self::once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverFormerArgumentsIfPreviousSlotDoesNotReturnAnything(): void
    {
        $firstMockSlot = $this->createMock(SlotFixture::class);
        $firstMockSlot->expects(self::once())
            ->method('slot')
            ->willReturnCallback(
                static function ($foo, $baz) {
                    return ['modified_' . $foo, 'modified_' . $baz];
                }
            );

        $secondMockSlot = $this->createMock(SlotFixture::class);
        $secondMockSlot->expects(self::once())
            ->method('slot');

        $thirdMockSlot = $this->createMock(SlotFixture::class);
        $thirdMockSlot->expects(self::once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $thirdMockSlot, 'slot');

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSlotReturnsNonArray(): void
    {
        $this->expectException(InvalidSlotReturnException::class);
        $this->expectExceptionCode(1376683067);

        $mockSlot = $this->createMock(SlotFixture::class);
        $mockSlot->expects(self::once())
            ->method('slot')
            ->willReturnCallback(
                static function () {
                    return 'string';
                }
            );

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSlotReturnsDifferentNumberOfItems(): void
    {
        $this->expectException(InvalidSlotReturnException::class);
        $this->expectExceptionCode(1376683066);

        $mockSlot = $this->createMock(SlotFixture::class);
        $mockSlot->expects(self::once())
            ->method('slot')
            ->willReturnCallback(
                static function () {
                    return [1, 2, 3];
                }
            );

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown(): void
    {
        $this->expectException(InvalidSlotException::class);
        $this->expectExceptionCode(1245673367);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', 'NonExistingClassName', 'slot', true);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', []);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist(): void
    {
        $this->expectException(InvalidSlotException::class);
        $this->expectExceptionCode(1245673368);
        $slotClassName = SlotMethodDoesNotExistFixture::class;
        $mockSlot = new SlotMethodDoesNotExistFixture();
        $this->objectManagerProphecy->get($slotClassName)->willReturn($mockSlot);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'unknownMethodName', true);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
        self::assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchPassesFirstArgumentContainingSlotInformationIfTheConnectionStatesSo(): void
    {
        $arguments = [];
        $mockSlot = static function () use (&$arguments) {
            $arguments = func_get_args();
        };
        $this->signalSlotDispatcher->connect('SignalClassName', 'methodName', $mockSlot, '', true);
        $this->signalSlotDispatcher->dispatch('SignalClassName', 'methodName', ['bar', 'quux']);
        self::assertSame(['bar', 'quux', 'SignalClassName::methodName'], $arguments);
    }

    /**
     * @test
     */
    public function connectThrowsInvalidArgumentExceptionIfSlotMethodNameIsEmptyAndSlotClassNameIsNoClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1229531659);
        $this->signalSlotDispatcher->connect('ClassA', 'emitSomeSignal', 'ClassB', '');
    }

    /**
     * @test
     */
    public function dispatchReturnsEmptyArrayIfSignalNameAndOrSignalClassNameIsNotRegistered(): void
    {
        self::assertSame([], $this->signalSlotDispatcher->dispatch('ClassA', 'someNotRegisteredSignalName'));
    }

    /**
     * @test
     */
    public function dispatchReturnsEmptyArrayIfSignalDoesNotProvideAnyArguments(): void
    {
        self::assertSame([], $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function dispatchReturnsArgumentsArrayAsIsIfSignalIsNotRegistered(): void
    {
        $arguments = [
            42,
            'a string',
            new \stdClass(),
        ];
        self::assertSame($arguments, $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal', $arguments));
    }
}
