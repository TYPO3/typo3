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
    public function validArrayForStringConfigurationProvider()
    {
        return [
            '12 in (12, 13, 14)' => [
                '12',
                '12,13,14',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            'Pizza in (Pizza, Lasange, Strogonvo)' => [
                'Pizza',
                'Pizza,Lasange,Strogonvo',
            ],
            'Rißtissen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Rißtissen',
                'Rißtissen,Überligen,Karlsruhe',
            ],

            '[12 and 14] in (12, 13, 14)' => [
                ['12', '14'],
                '12,13,14',
            ],
            '[1 and ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                ['1', 'ssd'],
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            '[Pizza and Strogonvo] in (Pizza, Lasange, Strogonvo)' => [
                ['Pizza', 'Strogonvo'],
                'Pizza,Lasange,Strogonvo',
            ],
            '[Rißtissen and Karlsruhe] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['Rißtissen', 'Karlsruhe'],
                'Rißtissen,Überligen,Karlsruhe',
            ],
        ];
    }

    /**
     * used for tests with valid input
     * will result in no errors returned
     *
     * @return array
     */
    public function validArrayForArrayConfigurationProvider()
    {
        return [
            '12 in [12, 13, 14]' => [
                '12',
                ['12', '13', '14'],
            ],
            '1 in [5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa]' => [
                '1',
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            'Pizza in [Pizza, Lasange, Strogonvo]' => [
                ['Pizza', 'Strogonvo'],
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            'Rißtissen in [Rißtissen, Überligen, Karlsruhe]' => [
                ['Rißtissen', 'Karlsruhe'],
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],

            '[12 and 14] in [12, 13, 14]' => [
                ['12', '14'],
                ['12', '13', '14'],
            ],
            '[1 and ssd] in [5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa]' => [
                ['1', 'ssd'],
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            '[Pizza and Strogonvo] in [Pizza, Lasange, Strogonvo]' => [
                'Pizza',
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            '[Rißtissen and Karlsruhe] in [Rißtissen, Überligen, Karlsruhe]' => [
                'Rißtissen',
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],
        ];
    }

    /**
     * used for test with invalid input
     * will result in errors returned
     *
     * @return array
     */
    public function invalidArrayForStringConfigurationProvider()
    {
        return [
            '12 in (11, 13, 14)' => [
                '12',
                '11,13,14',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,333434,1234,ssd,ysdfsa',
            ],
            'pizza in (Pizza, Lasange, Strogonvo)' => [
                'pizza',
                'Pizza,Lasange,Strogonvo',
            ],
            'Eimeldingen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Eimeldingen',
                'Rißtissen,Überligen,Karlsruhe',
            ],
            'überligen in (Rißtissen, Überligen, Karlsruhe)' => [
                'überligen',
                'Rißtissen,Überligen,Karlsruhe',
            ],

            '[12 and 14] in (11, 13, 14)' => [
                ['12', '14'],
                '11,13,14',
            ],
            '[1 and ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 333434, 1234, ssd, ysdfsa)' => [
                ['1', 'ssd'],
                '5,3234,oOIUoi8,3434,343,34,3,333434,1234,ssd,ysdfsa',
            ],
            '[pizza and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizza', 'Lasange'],
                'Pizza,Lasange,Strogonvo',
            ],
            '[Eimeldingen and Überligen] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['Eimeldingen', 'Überligen'],
                'Rißtissen,Überligen,Karlsruhe',
            ],
            '[Eimeldingen and überligen] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['Eimeldingen', 'überligen'],
                'Rißtissen,Überligen,Karlsruhe',
            ],
        ];
    }

    /**
     * used for test with invalid input
     * will result in errors returned
     *
     * @return array
     */
    public function invalidArrayForArrayConfigurationProvider()
    {
        return [
            '12 in (11, 13, 14)' => [
                '12',
                ['11', '13', '14'],
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            'pizza in (Pizza, Lasange, Strogonvo)' => [
                'pizza',
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            'Eimeldingen in (Rißtissen, Überligen, Karlsruhe)' => [
                'Eimeldingen',
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            'überligen in (Rißtissen, Überligen, Karlsruhe)' => [
                'überligen',
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],

            '[12 and 14] in (11, 13, 14)' => [
                ['12', '14'],
                ['11', '13', '14'],
            ],
            '[1 and ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 333434, 1234, ssd, ysdfsa)' => [
                ['1', 'ssd'],
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            '[pizza and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizza', 'Lasange'],
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            '[Eimeldingen and Überligen] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['Eimeldingen', 'Überligen'],
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            '[Eimeldingen and überligen] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['Eimeldingen', 'überligen'],
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
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
    public function validArrayForStringConfigurationIgnoreCaseProvider()
    {
        return [
            '12 in (12, 13, 14)' => [
                '12',
                '12,13,14',
            ],
            '1 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '1',
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            'pizza in (Pizza, Lasange, Strogonvo)' => [
                'pizza',
                'Pizza,Lasange,Strogonvo',
            ],
            'Pizza in (pizza, lasange, strogonvo)' => [
                'Pizza',
                'pizza,lasange,strogonvo',
            ],
            'Rißtissen in (rißtissen, Überligen, Karlsruhe)' => [
                'Rißtissen',
                'rißtissen,Überligen,Karlsruhe',
            ],
            'überligen in (Rißtissen, Überligen, Karlsruhe)' => [
                'überligen',
                'Rißtissen,Überligen,Karlsruhe',
            ],

            '[12 and 14] in (12, 13, 14)' => [
                ['12', '14'],
                '12,13,14',
            ],
            '[1 and Ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                ['1', 'Ssd'],
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            '[pizza and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizza', 'Lasange'],
                'Pizza,Lasange,Strogonvo',
            ],
            '[Pizza and lasange] in (pizza, lasange, strogonvo)' => [
                ['Pizza', 'lasange'],
                'pizza,lasange,strogonvo',
            ],
            '[Rißtissen and Karlsruhe] in (rißtissen, Überligen, Karlsruhe)' => [
                ['Rißtissen', 'Karlsruhe'],
                'rißtissen,Überligen,Karlsruhe',
            ],
            '[überligen and Karlsruhe] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['überligen', 'Karlsruhe'],
                'Rißtissen,Überligen,Karlsruhe',
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
    public function validArrayForArrayConfigurationIgnoreCaseProvider()
    {
        return [
            '12 in [12, 13, 14]' => [
                '12',
                ['12', '13', '14'],
            ],
            '1 in [5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa]' => [
                '1',
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            'pizza in [Pizza, Lasange, Strogonvo]' => [
                'pizza',
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            'Pizza in [pizza, lasange, strogonvo]' => [
                'Pizza',
                ['pizza', 'lasange', 'strogonvo'],
            ],
            'Rißtissen in (rißtissen, Überligen, Karlsruhe)' => [
                'Rißtissen',
                ['rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            'überligen in (Rißtissen, Überligen, Karlsruhe)' => [
                'überligen',
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],

            '[12 and 14] in (12, 13, 14)' => [
                ['12', '14'],
                ['12', '13', '14'],
            ],
            '[1 and Ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                ['1', 'Ssd'],
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            '[pizza and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizza', 'Lasange'],
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            '[Pizza and lasange] in (pizza, lasange, strogonvo)' => [
                ['Pizza', 'lasange'],
                ['pizza', 'lasange', 'strogonvo'],
            ],
            '[Rißtissen and Karlsruhe] in (rißtissen, Überligen, Karlsruhe)' => [
                ['Rißtissen', 'Karlsruhe'],
                ['rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            '[überligen and Karlsruhe] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['überligen', 'Karlsruhe'],
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
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
    public function invalidArrayForStringConfigurationIgnoreCaseProvider()
    {
        return [
            'zwölf in (12, 13, 14)' => [
                'zwölf',
                '12,13,14',
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

            '[zwölf and 14] in (12, 13, 14)' => [
                ['zwölf', '14'],
                '12,13,14',
            ],
            '[7 and Ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                ['7', 'Ssd'],
                '5,3234,oOIUoi8,3434,343,34,3,1,333434,1234,ssd,ysdfsa',
            ],
            '[riss and Karlsruhe] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['riss', 'Karlsruhe'],
                'Rißtissen,Überligen,Karlsruhe',
            ],
            '[pizzas and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizzas', 'Lasange'],
                'Pizza,Lasange,Strogonvo',
            ],
            '[lusange and Strogonvo] in (Pizza, Lasange, Strogonvo)' => [
                ['lusange', 'Strogonvo'],
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
    public function invalidArrayForArrayConfigurationIgnoreCaseProvider()
    {
        return [
            'zwölf in (12, 13, 14)' => [
                'zwölf',
                ['12', '13', '14'],
            ],
            '7 in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                '7',
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            'riss in (Rißtissen, Überligen, Karlsruhe)' => [
                'riss',
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            'pizzas in (Pizza, Lasange, Strogonvo)' => [
                'pizzas',
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            'lusange in (Pizza, Lasange, Strogonvo)' => [
                'lusange',
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],

            '[zwölf and 14] in (12, 13, 14)' => [
                ['zwölf', '14'],
                ['12', '13', '14'],
            ],
            '[7 and Ssd] in (5, 3234, oOIUoi8, 3434, 343, 34, 3, 1, 333434, 1234, ssd, ysdfsa)' => [
                ['7', 'Ssd'],
                ['5', '3234', 'oOIUoi8', '3434', '343', '34', '3', '1', '333434', '1234', 'ssd', 'ysdfsa'],
            ],
            '[riss and Karlsruhe] in (Rißtissen, Überligen, Karlsruhe)' => [
                ['riss', 'Karlsruhe'],
                ['Rißtissen', 'Überligen', 'Karlsruhe'],
            ],
            '[pizzas and Lasange] in (Pizza, Lasange, Strogonvo)' => [
                ['pizzas', 'Lasange'],
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
            '[lusange and Strogonvo] in (Pizza, Lasange, Strogonvo)' => [
                ['lusange', 'Strogonvo'],
                ['Pizza', 'Lasange', 'Strogonvo'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validArrayForStringConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnStringConfigurationReturnsNoErrors($value, $allowedOptionsString)
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
     * @dataProvider validArrayForArrayConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnArrayConfigurationReturnsNoErrors($value, $allowedOptionsString)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayForStringConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnStringConfigurationReturnsErrors($value, $allowedOptionsString)
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
     * @dataProvider invalidArrayForArrayConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnArrayConfigurationReturnsErrors($value, $allowedOptionsString)
    {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayForStringConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnStringConfigurationWithStrictComparisonReturnsNoErrors(
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
     * @dataProvider validArrayForArrayConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnArrayConfigurationWithStrictComparisonReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayForStringConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnStringConfigurationWithStrictComparisonReturnsErrors(
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
     * @dataProvider invalidArrayForArrayConfigurationProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnArrayConfigurationWithStrictComparisonReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayForStringConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnStringConfigurationWithIgnoreCaseReturnsNoErrors(
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
     * @dataProvider validArrayForArrayConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnArrayConfigurationWithIgnoreCaseReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayForStringConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnStringConfigurationWithIgnoreCaseReturnsErrors(
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
     * @dataProvider invalidArrayForArrayConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnArrayConfigurationWithIgnoreCaseReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider validArrayForStringConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnStringConfigurationWithIgnoreCaseAndStrictReturnsNoErrors(
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
     * @dataProvider validArrayForArrayConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForValidInputOnArrayConfigurationWithIgnoreCaseAndStrictReturnsNoErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertFalse($subject->validate($value)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidArrayForStringConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnStringConfigurationWithIgnoreCaseAndStrictReturnsErrors(
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

    /**
     * @test
     * @dataProvider invalidArrayForArrayConfigurationIgnoreCaseProvider
     *
     * @param string $value
     * @param string $allowedOptionsString
     */
    public function validateForInvalidInputOnArrayConfigurationWithIgnoreCaseAndStrictReturnsErrors(
        $value,
        $allowedOptionsString
    ) {
        $options = [
            'element' => uniqid('test'),
            'errorMessage' => uniqid('error'),
        ];
        $options['array.'] = $allowedOptionsString;
        $options['ignorecase'] = true;
        $options['strict'] = true;
        $subject = $this->createSubject($options);

        $this->assertTrue($subject->validate($value)->hasErrors());
    }
}
