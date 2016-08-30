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

    /**
     * @return array
     */
    public function validTypesProvider()
    {
        return [
            'pdf in (pdf)' => [
                'application/pdf',
                [
                    'type' => 'application/pdf',
                ],
            ],
            'pdf in (pdf, json)' => [
                'application/pdf, application/json',
                [
                    'type' => 'application/pdf',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function invalidTypesProvider()
    {
        return [
            'xml in (pdf, json)' => [
                'application/pdf, application/json',
                [
                    'type' => 'application/xml',
                ],
            ],
            'xml in (pdf)' => [
                'application/pdf',
                [
                    'type' => 'application/xml',
                ],
            ],
            'empty mimetype' => [
                'application/pdf, application/json',
                [
                    'type' => '',
                ],
            ],
            'empty value' => [
                'application/pdf, application/json',
                '',
            ],
        ];
    }

    /**
     * @test
     * @param string $types
     * @param array $value
     * @dataProvider validTypesProvider
     */
    public function validateForValidInputHasEmptyErrorResult($types, $value)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
            'types' => $types,
        ];
        $subject = $this->createSubject($options);

        $this->assertEmpty($subject->validate($value)->getErrors());
    }

    /**
     * @test
     * @param string $types
     * @param array $value
     * @dataProvider invalidTypesProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($types, $value)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
            'types' => $types,
        ];
        $subject = $this->createSubject($options);

        $this->assertNotEmpty($subject->validate($value)->getErrors());
    }
}
