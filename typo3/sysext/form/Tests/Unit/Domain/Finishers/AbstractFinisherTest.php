<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

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

use Prophecy\Argument;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Test case
 */
class AbstractFinisherTest extends \TYPO3\Components\TestingFramework\Core\UnitTestCase
{

    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameIsTranslation()
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $this->assertNull($mockAbstractFinisher->_call('parseOption', 'translation'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameNotExistsWithinOptions()
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', []);

        $this->assertNull($mockAbstractFinisher->_call('parseOption', 'foo'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameNotExistsWithinDefaultOptions()
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', []);

        $this->assertNull($mockAbstractFinisher->_call('parseOption', 'foo'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsArrayOptionValuesAsArray()
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'foo' => ['bar', 'foobar']
        ]);

        $expected = ['bar', 'foobar'];

        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'foo'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsValueFromFormRuntimeIfOptionNameReferenceAFormElementIdentifierWhoseValueIsAString()
    {
        $expected = 'element-value';
        $elementIdentifier = 'element-identifier-1';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}'
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::exact($elementIdentifier))->willReturn(true);
        $formRuntimeProphecy->offsetGet(Argument::exact($elementIdentifier))->willReturn($expected);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNoReplacedValueFromFormRuntimeIfOptionNameReferenceAFormElementIdentifierWhoseValueIsNotAString()
    {
        $elementIdentifier = 'element-identifier-1';
        $expected = '{' . $elementIdentifier . '}';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}'
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::exact($elementIdentifier))->willReturn(true);
        $formElementValue = new \DateTime;
        $formRuntimeProphecy->offsetGet(Argument::exact($elementIdentifier))->willReturn($formElementValue);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNoReplacedValueFromFormRuntimeIfOptionNameReferenceANonExistingFormElement()
    {
        $elementIdentifier = 'element-identifier-1';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}'
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::cetera())->willReturn(true);
        $formRuntimeProphecy->offsetGet(Argument::cetera())->willReturn(false);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $expected = '{' . $elementIdentifier . '}';
        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsDefaultOptionValueIfOptionNameNotExistsWithinOptionsButWithinDefaultOptions()
    {
        $expected = 'defaultValue';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', []);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::cetera())->willReturn(true);
        $formRuntimeProphecy->offsetGet(Argument::cetera())->willReturn(false);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsDefaultOptionValueIfOptionValueIsAFormElementReferenceAndTheFormElementValueIsEmpty()
    {
        $elementIdentifier = 'element-identifier-1';
        $expected = 'defaultValue';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}'
        ]);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::exact($elementIdentifier))->willReturn(true);
        $formRuntimeProphecy->offsetGet(Argument::exact($elementIdentifier))->willReturn('');

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $this->assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }
}
