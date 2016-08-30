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
class FileMinimumSizeValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\FileMinimumSizeValidator::class;

    protected function setUp()
    {
        $this->markTestSkipped('validate() instead of isValid() needs to be used');
    }

    public function validSizesProvider()
    {
        return [
            '12B for min. 11B' => [[11, 12]],
            '12B for min. 12B' => [[12, 12]]
        ];
    }

    public function invalidSizesProvider()
    {
        return [
            '11B for min. 12B' => [[12, 11]]
        ];
    }

    /**
     * @test
     * @dataProvider validSizesProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidSizesProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $options['minimum'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
