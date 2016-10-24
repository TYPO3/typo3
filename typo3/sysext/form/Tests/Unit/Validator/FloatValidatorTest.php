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
class FloatValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\FloatValidator::class;

    /**
     * @return array
     */
    public function validFloatProvider()
    {
        return [
            '12.1 for en_US locale' => [
                '12.1',
                'en_US.utf8',
            ],
            '12,1 for de_DE locale' => [
                '12,1',
                'de_DE.utf8',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validFloatProvider
     */
    public function validateForValidInputHasEmptyErrorResult($inputValue, $locale)
    {
        try {
            $this->setLocale(LC_NUMERIC, $locale);
        } catch (\PHPUnit_Framework_Exception $e) {
            $this->markTestSkipped('Locale ' . $locale . ' is not available.');
        }

        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($inputValue)->getErrors()
        );
    }
}
