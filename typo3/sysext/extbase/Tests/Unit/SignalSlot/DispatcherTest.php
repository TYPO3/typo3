<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\SignalSlot;

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

use TYPO3\CMS\Extbase\Tests\Unit\SignalSlot\Fixtures\OnlyClassNameSpecifiedFixture;
use TYPO3\CMS\Extbase\Tests\Unit\SignalSlot\Fixtures\SlotMethodDoesNotExistFixture;

/**
 * Test case
 */
class DispatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $signalSlotDispatcher;

    protected function setUp()
    {
        $accessibleClassName = $this->getAccessibleMock(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class, ['dummy']);
        $this->signalSlotDispatcher = new $accessibleClassName();
    }

    /**
     * @test
     */
    public function connectAllowsForConnectingASlotWithASignal()
    {
        $mockSignal = $this->getMock('ClassA', ['emitSomeSignal']);
        $mockSlot = $this->getMock('ClassB', ['someSlotMethod']);
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', true);
        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => true]
        ];
        $this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsObjectsInPlaceOfTheClassName()
    {
        $mockSignal = $this->getMock('ClassA', ['emitSomeSignal']);
        $mockSlot = $this->getMock('ClassB', ['someSlotMethod']);
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod', true);
        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => true]
        ];
        $this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsClosuresActingAsASlot()
    {
        $mockSignal = $this->getMock('ClassA', ['emitSomeSignal']);
        $mockSlot = function () {
        };
        $this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo', true);
        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => true]
        ];
        $this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheSlotMethod()
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            ($arguments = func_get_args());
        };
        $this->signalSlotDispatcher->connect('Foo', 'bar', $mockSlot, null, false);
        $this->signalSlotDispatcher->dispatch('Foo', 'bar', ['bar', 'quux']);
        $this->assertSame(['bar', 'quux'], $arguments);
    }

    /**
     * @test
     */
    public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified()
    {
        $slotClassName = OnlyClassNameSpecifiedFixture::class;
        $mockSlot = new OnlyClassNameSpecifiedFixture();
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(true));
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));
        $this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
        $this->signalSlotDispatcher->_set('isInitialized', true);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
        $this->assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverArgumentsReturnedByAFormerSlot()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);

        $firstMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $firstMockSlot->expects($this->once())
            ->method('slot')
            ->will($this->returnCallback(
                        function ($foo, $baz) {
                            return ['modified_' . $foo, 'modified_' . $baz];
                        }
                    ));

        $secondMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $secondMockSlot->expects($this->once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot', false);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot', false);

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverArgumentsReturnedByAFormerSlotWithoutInterferingWithSignalSlotInformation()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);

        $firstMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $firstMockSlot->expects($this->once())
            ->method('slot')
            ->will($this->returnCallback(
                        function ($foo, $baz) {
                            return ['modified_' . $foo, 'modified_' . $baz];
                        }
                    ));

        $secondMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $secondMockSlot->expects($this->once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchHandsOverFormerArgumentsIfPreviousSlotDoesNotReturnAnything()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);

        $firstMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $firstMockSlot->expects($this->once())
            ->method('slot')
            ->will($this->returnCallback(
                        function ($foo, $baz) {
                            return ['modified_' . $foo, 'modified_' . $baz];
                        }
                    ));

        $secondMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $secondMockSlot->expects($this->once())
            ->method('slot');

        $thirdMockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $thirdMockSlot->expects($this->once())
            ->method('slot')
            ->with('modified_bar', 'modified_quux');

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $thirdMockSlot, 'slot');

        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function dispatchThrowsAnExceptionIfTheSlotReturnsNonArray()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);

        $mockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $mockSlot->expects($this->once())
            ->method('slot')
            ->will($this->returnCallback(
                        function () {
                            return 'string';
                        }
                    ));

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function dispatchThrowsAnExceptionIfTheSlotReturnsDifferentNumberOfItems()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);

        $mockSlot = $this->getMock(\TYPO3\CMS\Extbase\Tests\Fixture\SlotFixture::class);
        $mockSlot->expects($this->once())
            ->method('slot')
            ->will($this->returnCallback(
                        function () {
                            return [1, 2, 3];
                        }
                    ));

        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', false);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown()
    {
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(false));
        $this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
        $this->signalSlotDispatcher->_set('isInitialized', true);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', 'NonExistingClassName', 'slot', true);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', []);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist()
    {
        $slotClassName = SlotMethodDoesNotExistFixture::class;
        $mockSlot = new SlotMethodDoesNotExistFixture();
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(true));
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));
        $this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
        $this->signalSlotDispatcher->_set('isInitialized', true);
        $this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'unknownMethodName', true);
        $this->signalSlotDispatcher->dispatch('Foo', 'emitBar', ['bar', 'quux']);
        $this->assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchPassesFirstArgumentContainingSlotInformationIfTheConnectionStatesSo()
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            ($arguments = func_get_args());
        };
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->signalSlotDispatcher->connect('SignalClassName', 'methodName', $mockSlot, null, true);
        $this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
        $this->signalSlotDispatcher->_set('isInitialized', true);
        $this->signalSlotDispatcher->dispatch('SignalClassName', 'methodName', ['bar', 'quux']);
        $this->assertSame(['bar', 'quux', 'SignalClassName::methodName'], $arguments);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function connectThrowsInvalidArgumentExceptionIfSlotMethodNameIsEmptyAndSlotClassNameIsNoClosure()
    {
        $this->signalSlotDispatcher->connect('ClassA', 'emitSomeSignal', 'ClassB', '');
    }

    /**
     * @test
     */
    public function dispatchReturnsEmptyArrayIfSignalNameAndOrSignalClassNameIsNotRegistered()
    {
        $this->assertSame([], $this->signalSlotDispatcher->dispatch('ClassA', 'someNotRegisteredSignalName'));
    }

    /**
     * @test
     */
    public function dispatchReturnsEmptyArrayIfSignalDoesNotProvideAnyArguments()
    {
        $this->assertSame([], $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal'));
    }

    /**
     * @test
     */
    public function dispatchReturnsArgumentsArrayAsIsIfSignalIsNotRegistered()
    {
        $arguments = [
            42,
            'a string',
            new \stdClass()
        ];
        $this->assertSame($arguments, $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal', $arguments));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    public function dispatchThrowsInvalidSlotExceptionIfObjectManagerOfSignalSlotDispatcherIsNotSet()
    {
        $this->signalSlotDispatcher->_set('isInitialized', true);
        $this->signalSlotDispatcher->_set('objectManager', null);
        $this->signalSlotDispatcher->_set('slots', ['ClassA' => ['emitSomeSignal' => [[]]]]);

        $this->assertSame(null, $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal'));
    }
}
