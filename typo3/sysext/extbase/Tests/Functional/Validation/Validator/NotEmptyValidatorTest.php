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
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NotEmptyValidatorTest extends FunctionalTestCase
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
    public function notEmptyValidatorReturnsNoErrorForASimpleString(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertFalse($validator->validate('a not empty string')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForAnEmptyString(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorReturnsErrorForANullValue(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertCount(1, $validator->validate('')->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorCreatesTheCorrectErrorForANullValue(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertCount(1, $validator->validate(null)->getErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyArrays(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate([])->hasErrors());
        self::assertFalse($validator->validate([1 => 2])->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForEmptyCountableObjects(): void
    {
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate(new \SplObjectStorage())->hasErrors());
    }

    /**
     * @test
     */
    public function notEmptyValidatorWorksForNotEmptyCountableObjects(): void
    {
        $countableObject = new \SplObjectStorage();
        $countableObject->attach(new \stdClass());
        $validator = new NotEmptyValidator();
        $validator->setOptions([]);
        self::assertFalse($validator->validate($countableObject)->hasErrors());
    }
}
