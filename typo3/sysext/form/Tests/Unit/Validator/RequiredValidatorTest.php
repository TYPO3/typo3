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
        return array(
            'string "a"'   => array('a'),
            'string "a b"' => array('a b'),
            'string "0"'   => array('0'),
            'value 0'      => array(0),
            'array with string "a"'   => array(array('a')),
            'array with string "a b"' => array(array('a b')),
            'array with string "0"'   => array(array('0')),
            'array with value 0'      => array(array(0)),
            'array with strings "a" and "b"' => array(array('a', 'b')),
            'array with empty string and "a"' => array(array('', 'a')),
            'array with empty string and "0"' => array(array('', '0')),
            'array with empty string and 0' => array(array('', 0)),
        );
    }

    /**
     * @return array
     */
    public function invalidDataProvider()
    {
        return array(
            'empty string'            => array(''),
            'array with empty string' => array(array('')),
            'array with empty strings' => array(array('', ''))
        );
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function validateForValidDataHasEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
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
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
