<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BooleanValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseStringExpectation(): void
    {
        $options = ['is' => 'false'];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueStringExpectation(): void
    {
        $options = ['is' => 'true'];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueExpectation(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseExpectation(): void
    {
        $options = ['is' => false];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForTrueWhenFalseExpected(): void
    {
        $options = ['is' => false];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForFalseWhenTrueExpected(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForAString(): void
    {
        $options = ['is' => true];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('a string')->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsTrueIfNoParameterIsGiven(): void
    {
        $options = [];
        $validator = new BooleanValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(true)->hasErrors());
    }
}
