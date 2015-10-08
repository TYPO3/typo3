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
        return array(
            'ascii without spaces' => array('thisismyinput4711'),
            'accents without spaces' => array('éóéàèò4711'),
            'umlauts without spaces' => array('üöä4711'),
            'empty string' => array('')
        );
    }

    /**
     * @return array
     */
    public function validDataProviderWithWhitespace()
    {
        return array(
            'ascii with spaces' => array('This is my input 4711'),
            'accents with spaces' => array('Sigur Rós 4711'),
            'umlauts with spaces' => array('Hürriyet Daily News 4711'),
            'space' => array(' '),
            'empty string' => array('')
        );
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithoutWhitespace()
    {
        return array(
            'ascii with dash' => array('my-name-4711'),
            'accents with underscore' => array('Sigur_Rós_4711'),
            'umlauts with periods' => array('Hürriyet.Daily.News.4711'),
            'space' => array(' '),
        );
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithWhitespace()
    {
        return array(
            'ascii with spaces and dashes' => array('This is my-name 4711'),
            'accents with spaces and underscores' => array('Listen to Sigur_Rós_Band 4711'),
            'umlauts with spaces and periods' => array('Go get the Hürriyet.Daily.News 4711')
        );
    }

    /**
     * @param string $input
     * @test
     * @dataProvider validDataProviderWithoutWhitespace
     */
    public function validateForValidInputWithoutAllowedWhitespaceHasEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
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
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true);
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
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
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
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true);
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
