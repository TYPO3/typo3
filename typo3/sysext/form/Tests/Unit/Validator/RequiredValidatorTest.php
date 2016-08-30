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

/**
 * Test case
 */
class RequiredValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\RequiredValidator::class;

    /**
     * @return array
     */
    public function validDataProvider()
    {
        return [
            'string "a"'   => ['a'],
            'string "a b"' => ['a b'],
            'string "0"'   => ['0'],
            'value 0'      => [0],
            'array with string "a"'   => [['a']],
            'array with string "a b"' => [['a b']],
            'array with string "0"'   => [['0']],
            'array with value 0'      => [[0]],
            'array with strings "a" and "b"' => [['a', 'b']],
            'array with empty string and "a"' => [['', 'a']],
            'array with empty string and "0"' => [['', '0']],
            'array with empty string and 0' => [['', 0]],
        ];
    }

    /**
     * @return array
     */
    public function invalidDataProvider()
    {
        return [
            'empty string'            => [''],
            'array with empty string' => [['']],
            'array with empty strings' => [['', '']]
        ];
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function validateForValidDataHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function validateForInvalidDataHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
