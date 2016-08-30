<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validator;

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
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Form\Domain\Validator\LengthValidator;

/**
 * Test case
 */
class LengthValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\LengthValidator::class;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    protected $charsetConverterProphecy;

    /**
     * Set up
     */
    protected function setUp()
    {
        parent::setUp();
        $this->charsetConverterProphecy = $this->prophesize(CharsetConverter::class);
        $this->charsetConverterProphecy
            ->strlen(Argument::cetera())
            ->will(function ($arguments) {
                return mb_strlen($arguments[1], $arguments[0]);
            });
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->charsetConverterProphecy);
    }

    /**
     * @return array
     */
    public function validLengthProvider()
    {
        return [
            '4 ≤ length(myString) ≤ 8' => [
                [4, 8, 'mäString']
            ],
            '8 ≤ length(myString) ≤ 8' => [
                [8, 8, 'möString']
            ],
            '4 ≤ length(myString)' => [
                [4, null, 'myString']
            ],
            '4 ≤ length(asdf) ≤ 4' => [
                [4, 4, 'asdf']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validLengthProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[1];
        /** @var LengthValidator $subject */
        $subject = $this->createSubject($options);
        $subject->injectCharsetConverter($this->charsetConverterProphecy->reveal());

        $this->assertEmpty(
            $subject->validate($input[2])->getErrors()
        );
    }

    /**
     * @return array
     */
    public function invalidLengthProvider()
    {
        return [
            '4 ≤ length(my) ≤ 12' => [
                [4, 12, 'my']
            ],
            '4 ≤ length(my long string) ≤ 12' => [
                [4, 12, 'my long string']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidLengthProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[1];
        /** @var LengthValidator $subject */
        $subject = $this->createSubject($options);
        $subject->injectCharsetConverter($this->charsetConverterProphecy->reveal());

        $this->assertNotEmpty(
            $subject->validate($input[2])->getErrors()
        );
    }
}
