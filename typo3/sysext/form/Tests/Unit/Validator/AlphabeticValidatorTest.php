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
class AlphabeticValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\AlphabeticValidator::class;

    /**
     * @return array
     */
    public function validDataProviderWithoutWhitespace()
    {
        return [
            'ascii without spaces' => ['thisismyinput'],
            'accents without spaces' => ['éóéàèò'],
            'umlauts without spaces' => ['üöä'],
            'empty string' => ['']
        ];
    }

    /**
     * @return array
     */
    public function validDataProviderWithWhitespace()
    {
        return [
            'ascii with spaces' => ['This is my input'],
            'accents with spaces' => ['Sigur Rós'],
            'umlauts with spaces' => ['Hürriyet Daily News'],
            'space' => [' '],
            'empty string' => ['']
        ];
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithoutWhitespace()
    {
        return [
            'ascii with dash' => ['my-name'],
            'accents with underscore' => ['Sigur_Rós'],
            'umlauts with periods' => ['Hürriyet.Daily.News'],
            'space' => [' '],
        ];
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithWhitespace()
    {
        return [
            'ascii with spaces and dashes' => ['This is my-name'],
            'accents with spaces and underscores' => ['Listen to Sigur_Rós_Band'],
            'umlauts with spaces and periods' => ['Go get the Hürriyet.Daily.News']
        ];
    }

    /**
     * @param string $input
     * @test
     * @dataProvider validDataProviderWithoutWhitespace
     */
    public function validateForValidInputWithoutAllowedWhitespaceHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @param string $input
     * @test
     * @dataProvider validDataProviderWithWhitespace
     */
    public function validateForValidInputWithWhitespaceAllowedHasEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @param string $input
     * @test
     * @dataProvider invalidDataProviderWithoutWhitespace
     */
    public function validateForInvalidInputWithoutAllowedWhitespaceHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error')];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @param string $input
     * @test
     * @dataProvider invalidDataProviderWithWhitespace
     */
    public function validateForInvalidInputWithWhitespaceAllowedHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
