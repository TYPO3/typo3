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
class DateValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\DateValidator::class;

    /**
     * @return array
     */
    public function validDateProvider()
    {
        return [
            '28-03-2012' => [['%e-%m-%Y', '28-03-2012']],
            '8-03-2012'  => [['%e-%m-%Y', '8-03-2012']],
            '29-02-2012' => [['%d-%m-%Y', '29-02-2012']]
        ];
    }

    /**
     * @return array
     */
    public function invalidDateProvider()
    {
        return [
            '32-03-2012' => [['%d-%m-%Y', '32-03-2012']],
            '31-13-2012' => [['%d-%m-%Y', '31-13-2012']],
            '29-02-2011' => [['%d-%m-%Y', '29-02-2011']]
        ];
    }

    /**
     * @test
     * @dataProvider validDateProvider
     * @param array $input
     */
    public function validateForValidInputHasEmptyErrorResult(array $input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['format'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidDateProvider
     * @param array $input
     */
    public function validateForInvalidInputHasNotEmptyErrorResult(array $input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['format'] = $input[0];
        $subject = $this->createSubject($options);

        $subject->expects($this->once())
            ->method('humanReadableDateFormat')
            ->willReturnArgument(0);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
