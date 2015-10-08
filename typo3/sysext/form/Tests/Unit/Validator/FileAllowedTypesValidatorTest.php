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
class FileAllowedTypesValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\FileAllowedTypesValidator::class;

    protected function setUp()
    {
        $this->markTestSkipped('Erros found in specific implementation, see @todo remarks there');
    }

    /**
     * @return array
     */
    public function validTypesProvider()
    {
        return array(
            'pdf in (pdf)'       => array(array('application/pdf', 'application/pdf')),
            'pdf in (pdf, json)' => array(array('application/pdf, application/json', 'application/pdf'))

        );
    }

    /**
     * @return array
     */
    public function invalidTypesProvider()
    {
        return array(
            'xml in (pdf, json)' => array(array('application/pdf, application/json', 'application/xml')),
            'xml in (pdf)'       => array(array('application/pdf, application/json', 'application/xml'))
        );
    }

    /**
     * @test
     * @dataProvider validTypesProvider
     */
    public function validateForValidInputHasEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $options['types'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }

    /**
     * @test
     * @dataProvider invalidTypesProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($input)
    {
        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $options['types'] = $input[0];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($input[1])->getErrors()
        );
    }
}
