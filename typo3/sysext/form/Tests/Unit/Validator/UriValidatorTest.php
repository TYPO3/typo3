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
class UriValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\UriValidator::class;

    /**
     * @return array
     */
    public function validDataProvider()
    {
        return array(
            'http://example.net'              => array('http://example.net'),
            'https://example.net'             => array('https://example.net'),
            'http://a:b@example.net'          => array('http://a:b@example.net'),
        );
    }

    /**
     * @return array
     */
    public function invalidDataProvider()
    {
        return array(
            'index.php' => array('index.php')
        );
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input)->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input)->getErrors()
        );
    }
}
