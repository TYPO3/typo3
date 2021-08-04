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

namespace TYPO3\CMS\Backend\Tests\Unit\Form;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormDataCompilerTest extends UnitTestCase
{
    use ProphecyTrait;

    protected FormDataCompiler $subject;

    /**
     * @var FormDataGroupInterface|ObjectProphecy
     */
    protected ObjectProphecy $formDataGroupProphecy;

    protected function setUp(): void
    {
        $this->formDataGroupProphecy = $this->prophesize(FormDataGroupInterface::class);
        $this->subject = new FormDataCompiler($this->formDataGroupProphecy->reveal());
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfInputContainsKeysNotValidInResult(): void
    {
        $input = [
            'foo' => 'bar',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1440601540);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionAtUnknownCommand(): void
    {
        $input = [
            'command' => 'unknownCommand',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437653136);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfNoTableNameGiven(): void
    {
        $input = [
            'tableName' => '',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654409);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfUidIsNotAnInteger(): void
    {
        $input = [
            'vanillaUid' => 'foo123',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654247);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfCommandIsEditAndUidIsNegative(): void
    {
        $input = [
            'command' => 'edit',
            'vanillaUid' => -100,
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654332);
        $this->subject->compile($input);
    }

    /**
     * @test
     */
    public function compileReturnsResultArrayWithInputDataSet(): void
    {
        $input = [
            'tableName' => 'pages',
            'vanillaUid' => 123,
            'command' => 'edit',
        ];
        $this->formDataGroupProphecy->compile(Argument::cetera())->willReturnArgument(0);
        $result = $this->subject->compile($input);
        self::assertEquals('pages', $result['tableName']);
        self::assertEquals(123, $result['vanillaUid']);
        self::assertEquals('edit', $result['command']);
    }

    /**
     * @test
     */
    public function compileReturnsResultArrayWithAdditionalDataFormFormDataGroup(): void
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(static function ($arguments) {
            $result = $arguments[0];
            $result['databaseRow'] = 'newData';
            return $result;
        });
        $result = $this->subject->compile([]);
        self::assertEquals('newData', $result['databaseRow']);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupDoesNotReturnArray(): void
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->willReturn(null);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1446664764);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfRenderDataIsNotEmpty(): void
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->willReturn([
            'renderData' => [ 'foo' ],
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1485201279);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupRemovedKeysFromResultArray(): void
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(static function ($arguments) {
            $result = $arguments[0];
            unset($result['tableName']);
            return $result;
        });
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438079402);
        $this->subject->compile([]);
    }

    /**
     * @test
     */
    public function compileThrowsExceptionIfFormDataGroupAddedKeysToResultArray(): void
    {
        $this->formDataGroupProphecy->compile(Argument::cetera())->will(static function ($arguments) {
            $result = $arguments[0];
            $result['newKey'] = 'newData';
            return $result;
        });
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438079402);
        $this->subject->compile([]);
    }
}
