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
use TYPO3\CMS\Form\Domain\Validator\InArrayValidator;

/**
 * Test case
 */
class InArrayValidatorTest extends AbstractValidatorTest
{

    /**
     * @var string
     */
    protected $subjectClassName = InArrayValidator::class;

    /**
     * used for tests with valid input
     * will result in no errors returned
     *
     * @return array
     */
    public function validArrayProvider()
    {
        return [
            '12 in (12, 13)' => [
                '12',
                '12,13',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            'Rißtissen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Rißtissen',
                'Rißtissen,Überligen,Karlsruhe',
            ],
            'Pizza in (Pizza, Lasange, Strogonvo)' => [
                'Pizza',
                'Pizza,Lasange,Strogonvo',
            ],
        ];
    }

    /**
     * used for test with invalid input
     * will result in errors returned
     *
     * @return array
     */
    public function invalidArrayProvider()
    {
        return [
            '12 in (11, 13)' => [
                '12',
                '11,13',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,333434,1234,ssd,ysdfsa',
            ],
            'Eimeldingen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Eimeldingen',
                'Rißtissen,Überligen,Karlsruhe',
            ],
            'pizza in (Pizza, Lasange, Strogonvo)' => [
                'pizza',
                'Pizza,Lasange,Strogonvo',
            ],
        ];
    }

    /**
     * used for tests with valid input
     * ignorecase is set to true
     * results in no errors returned
     *
     * @return array
     */
    public function validArrayIgnoreCaseProvider()
    {
        return [
            '12 in (12, 13)' => [
                '12',
                '12,13',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            'Rißtissen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Rißtissen',
                'Rißtissen,Überligen,Karlsruhe',
            ],
            'überlingen in (Rißtissen, Überligen, Karlsruhe)' => [
                'überlingen',
                'Rißtissen,Überlingen,Karlsruhe',
            ],
            'Österreich in (österreich, deutschland, schweiz)' => [
                'Österreich',
                'österreich,deutschland,schweiz',
            ],
            'pizza in (Pizza, Lasange, Strogonvo)' => [
                'pizza',
                'Pizza,Lasange,Strogonvo',
            ],
            'lasange in (Pizza, Lasange, Strogonvo)' => [
                'lasange',
                'Pizza,Lasange,Strogonvo',
            ],
        ];
    }

    /**
     * used for tests with invalid input
     * ignorecase is set to true
     * results in errors returned
     *
     * @return array
     */
    public function invalidArrayIgnoreCaseProvider()
    {
        return [
            'zwölf in (12, 13)' => [
                'zwölf',
                '12,13',
            ],
            '7 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '7',
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            'riss in (Rißtissen, Überligen, Karlsruhe)' => [
                'riss',
                'Rißtissen,Überligen,Karlsruhe',
            ],
            'pizzas in (Pizza, Lasange, Strogonvo)' => [
                'pizzas',
                'Pizza,Lasange,Strogonvo',
            ],
            'lusange in (Pizza, Lasange, Strogonvo)' => [
                'lusange',
                'Pizza,Lasange,Strogonvo',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validArrayProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputReturnsNoErrors($value, $allowedOptionsString)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputReturnsErrors($value, $allowedOptionsString)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputWithStrictComparisonReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputWithStrictComparisonReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputWithIgnoreCaseReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputWithIgnoreCaseReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputWithIgnoreCaseAndStrictReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputWithIgnoreCaseAndStrictReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }
}
