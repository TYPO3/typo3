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
        return array(
            'ascii without spaces' => array('thisismyinput'),
            'accents without spaces' => array('éóéàèò'),
            'umlauts without spaces' => array('üöä'),
            'empty string' => array('')
        );
    }

    /**
     * @return array
     */
    public function validDataProviderWithWhitespace()
    {
        return array(
            'ascii with spaces' => array('This is my input'),
            'accents with spaces' => array('Sigur Rós'),
            'umlauts with spaces' => array('Hürriyet Daily News'),
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
            'ascii with dash' => array('my-name'),
            'accents with underscore' => array('Sigur_Rós'),
            'umlauts with periods' => array('Hürriyet.Daily.News'),
            'space' => array(' '),
        );
    }

    /**
     * @return array
     */
    public function invalidDataProviderWithWhitespace()
    {
        return array(
            'ascii with spaces and dashes' => array('This is my-name'),
            'accents with spaces and underscores' => array('Listen to Sigur_Rós_Band'),
            'umlauts with spaces and periods' => array('Go get the Hürriyet.Daily.News')
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
    public function validateForValidInputWithWhitespaceAllowedHasEmptyErrorResult($input)
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
    public function validateForInvalidInputWithWhitespaceAllowedHasNotEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'), 'allowWhiteSpace' => true);
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
