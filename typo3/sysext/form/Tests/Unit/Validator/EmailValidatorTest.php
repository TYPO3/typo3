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
class EmailValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\EmailValidator::class;

    /**
     * @return array
     */
    public function validEmailProvider()
    {
        return [
            'a@b.de' => ['a@b.de'],
            'somebody@mymac.local' => ['somebody@mymac.local'],
            'empty value' => [''],
            'unexpected value' => [[]],
        ];
    }

    /**
     * @return array
     */
    public function invalidEmailProvider()
    {
        return [
            'myemail@' => ['myemail@'],
            'myemail' => ['myemail'],
            'somebody@localhost' => ['somebody@localhost'],
        ];
    }

    /**
     * @test
     * @dataProvider validEmailProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidEmailProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
