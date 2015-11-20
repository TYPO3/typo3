<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class FormDataCompilerTest extends UnitTestCase
{
    /**
     * @var FormDataCompiler
     */
    protected $subject;

    /**
     * @var FormDataGroupInterface | ObjectProphecy
     */
    protected $formDataGroupProphecy;

    protected function setUp()
    {
        $this->formDataGroupProphecy = $this->prophesize(FormDataGroupInterface::class);
        $this->subject = new FormDataCompiler($this->formDataGroupProphecy->reveal());
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfInputContainsKeysNotValidInResult()
    {
        $input = [
            'foo' => 'bar',
        ];
        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1440601540);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionAtUnknownCommand()
    {
        $input = [
            'command' => 'unknownCommand',
        ];
        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437653136);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfNoTableNameGiven()
    {
        $input = [
            'tableName' => '',
        ];
        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437654409);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfUidIsNotAnInteger()
    {
        $input = [
            'vanillaUid' => 'foo123',
        ];
        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437654247);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfCommandIsEditAndUidIsNegative()
    {
        $input = [
            'command' => 'edit',
            'vanillaUid' => -100,
        ];
        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437654332);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileReturnsResultArrayWithInputDataSet()
    {
        $input = [
            'tableName' => 'pages',
            'vanillaUid' => 123,
            'command' => 'edit',
        ];
        $this->formDataGroupProphecy->compile(Argument::cetera())->willReturnArgument(0);
        $result = $this->subject->compile($input);
        $this->assertEquals('pages', $result['tableName']);
        $this->assertEquals(123, $result['vanillaUid']);
        $this->assertEquals('edit', $result['command']);
    }

    /**
     * @test
     */
    public function compileReturnsResultArrayWithAdditionalDataFormFormDataGroup()
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(function ($arguments) {
            $result = $arguments[0];
            $result['databaseRow'] = 'newData';
            return $result;
        });
        $result = $this->subject->compile([]);
        $this->assertEquals('newData', $result['databaseRow']);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupDoesNotReturnArray()
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->willReturn(null);
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1446664764);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupRemovedKeysFromResultArray()
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(function ($arguments) {
            $result = $arguments[0];
            unset($result['tableName']);
            return $result;
        });
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438079402);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupAddedKeysToResultArray()
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(function ($arguments) {
            $result = $arguments[0];
            $result['newKey'] = 'newData';
            return $result;
        });
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438079402);
        $this->subject->compile([]);
    }
}
