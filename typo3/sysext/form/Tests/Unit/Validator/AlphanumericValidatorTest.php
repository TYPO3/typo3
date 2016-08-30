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
class AlphanumericValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\AlphanumericValidator::class;

    /**
     * @return array
     */
    public function validDataProviderWithoutWhitespace()
    {
        return [
            'ascii without spaces' => ['thisismyinput4711'],
            'accents without spaces' => ['éóéàèò4711'],
            'umlauts without spaces' => ['üöä4711'],
            'empty string' => ['']
        ];
    }

    /**
     * @return array
     */
    public function validDataProviderWithWhitespace()
    {
        return [
            'ascii with spaces' => ['This is my input 4711'],
            'accents with spaces' => ['Sigur Rós 4711'],
            'umlauts with spaces' => ['Hürriyet Daily News 4711'],
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
            'ascii with dash' => ['my-name-4711'],
            'accents with underscore' => ['Sigur_Rós_4711'],
            'umlauts with periods' => ['Hürriyet.Daily.News.4711'],
            'space' => [' '],
        ];
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithWhitespace()
    {
        return [
            'ascii with spaces and dashes' => ['This is my-name 4711'],
            'accents with spaces and underscores' => ['Listen to Sigur_Rós_Band 4711'],
            'umlauts with spaces and periods' => ['Go get the Hürriyet.Daily.News 4711']
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
    public function validateForValidInputWithAllowedWhitespaceHasEmptyErrorResult($input)
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
    public function validateForInvalidInputWithAllowedWhitespaceHasNotEmptyErrorResult($input)
    {
        $options = ['element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
