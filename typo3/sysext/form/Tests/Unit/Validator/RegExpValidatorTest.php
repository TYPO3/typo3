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
class RegExpValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\RegExpValidator::class;

    /**
     * @return array
     */
    public function validDataProvider()
    {
        return [
            '/^a/ matches a' => [['/^a/', 'a']],
        ];
    }

    /**
     * @return array
     */
    public function invalidDataProvider()
    {
        return [
            '/[^\d]/ matches 8' => [['/[^\d]/', 8]],
        ];
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['expression'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['expression'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
