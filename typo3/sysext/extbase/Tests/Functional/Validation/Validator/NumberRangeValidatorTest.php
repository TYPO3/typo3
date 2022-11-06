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
use TYPO3\CMS\Extbase\Validation\Validator\NumberRangeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumberRangeValidatorTest extends FunctionalTestCase
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
    public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange(): void
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = new NumberRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(10.5)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForANumberOutOfRange(): void
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = new NumberRangeValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(1000.1)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange(): void
    {
        $options = ['minimum' => 1000, 'maximum' => 0];
        $validator = new NumberRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate(100)->hasErrors());
    }

    /**
     * @test
     */
    public function numberRangeValidatorReturnsErrorForAString(): void
    {
        $options = ['minimum' => 0, 'maximum' => 1000];
        $validator = new NumberRangeValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('not a number')->hasErrors());
    }
}
