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
class EqualsValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\EqualsValidator::class;

    /**
     * @return array
     */
    public function validPairProvider()
    {
        return array(
            'something === something' => array(array('something', 'something')),
            '4 === 4'                 => array(array(4, 4))
        );
    }

    /**
     * @return array
     */
    public function invalidPairProvider()
    {
        return array(
            'somethingElse !== something' => array(array('somethingElse', 'something')),
            '4 !== 3'                     => array(array(4, 3))
        );
    }

    /**
     * @test
     * @dataProvider validPairProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $options['field'] = uniqid('field');
        $subject = $this->createSubject($options);
        $subject->setRawArgument(array($options['field'] => $input[0]));

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidPairProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $options['field'] = uniqid('field');
        $subject = $this->createSubject($options);
        $subject->setRawArgument(array($options['field'] => $input[0]));

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
