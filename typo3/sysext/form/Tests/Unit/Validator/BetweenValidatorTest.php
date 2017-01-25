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
class BetweenValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\BetweenValidator::class;

    /**
     * @return array
     */
    public function validNonInclusiveDataProvider()
    {
        return [
            '3 < 5 < 7'      => [[3, 5, 7]],
            '0 < 10 < 20'    => [[0, 10, 20]],
            '-10 < 0 < 10'   => [[-10, 0, 10]],
            '-20 < -10 < 0'  => [[-20, -10, 0]],
            '1 < 2 < 3'      => [[1, 2, 3]],
            '1 < 1.01 < 1.1' => [[1, 1.01, 1.1]],
        ];
    }

    /**
     * @return array
     */
    public function invalidNonInclusiveDataProvider()
    {
        return [
            '1 < 1 < 2'                 => [[1, 1, 2]],
            '1 < 2 < 2'                 => [[1, 2, 2]],
            '1.1 < 1.1 < 1.2'           => [[1.1, 1.1, 1.2]],
            '1.1 < 1.2 < 1.2'           => [[1.1, 1.2, 1.2]],
            '-10.1234 < -10.12340 < 10' => [[-10.1234, -10.12340, 10]],
            '100 < 0 < -100'            => [[100, 0, -100]]
        ];
    }

    /**
     * @return array
     */
    public function validInclusiveDataProvider()
    {
        return [
            '1 ≤ 1 ≤ 1'                 => [[1, 1, 1]],
            '-10.1234 ≤ -10.12340 ≤ 10' => [[-10.1234, -10.12340, 10]],
            '-10.1234 ≤ -10 ≤ 10'       => [[-10.1234, -10.12340, 10]],
        ];
    }

    public function invalidInclusiveDataProvider()
    {
        return [
            '-10.1234 ≤ -10.12345 ≤ 10' => [[-10.1234, -10.12345, 10]],
            '100 ≤ 0 ≤ -100'            => [[100, 0, -100]]
        ];
    }

    /**
     * @param array $input
     * @test
     * @dataProvider validNonInclusiveDataProvider
     */
    public function validateWithValidInputAndWithoutInclusiveHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[2];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @param array $input
     * @test
     * @dataProvider validInclusiveDataProvider
     */
    public function validateWithValidInputAndWithInclusiveHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[2];
        $options['inclusive'] = true;
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @param array $input
     * @test
     * @dataProvider invalidNonInclusiveDataProvider
     */
    public function validateWithInvalidInputAndWithoutInclusiveHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[2];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @param array $input
     * @test
     * @dataProvider invalidInclusiveDataProvider
     */
    public function validateWithInvalidInputAndWithInclusiveHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $options['maximum'] = $input[2];
        $options['inclusive'] = true;
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
