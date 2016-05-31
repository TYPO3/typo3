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
class IntegerValidatorTest extends AbstractValidatorTest
{
    /**
     * @var string
     */
    protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\IntegerValidator::class;

    /**
     * @var string
     */
    protected $currentLocale;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->currentLocale = setlocale(LC_NUMERIC, 0);
    }

    /**
     * Tears down this test case.
     */
    protected function tearDown()
    {
        setlocale(LC_NUMERIC, $this->currentLocale);
        parent::tearDown();
    }

    public function validateForValidInputHasEmptyErrorResultDataProvider()
    {
        return [
            '12 for de locale' => [
                12,
                'de_DE.utf8'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validateForValidInputHasEmptyErrorResultDataProvider
     */
    public function validateForValidInputHasEmptyErrorResult($value, $locale)
    {
        setlocale(LC_NUMERIC, $locale);

        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $subject = $this->createSubject($options);

        $this->assertEmpty(
            $subject->validate($value)->getErrors()
        );
    }

    public function validateForInvalidInputHasNotEmptyErrorResultDataProvider()
    {
        return [
            '12.1 for en_US locale' => [
                12.1,
                'en_US.utf8'
            ],
            // @todo de_DE disabled currently, works locally but not on travis-ci.org
            /**
            '12,1 for de_DE locale' => [
                '12,1',
                'de_DE.utf8'
            ],
             */
        ];
    }

    /**
     * @test
     * @dataProvider validateForInvalidInputHasNotEmptyErrorResultDataProvider
     */
    public function validateForInvalidInputHasNotEmptyErrorResult($value, $locale)
    {
        setlocale(LC_NUMERIC, $locale);

        $options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
        $subject = $this->createSubject($options);

        $this->assertNotEmpty(
            $subject->validate($value)->getErrors()
        );
    }
}
