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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Validation;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EmptyValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsEmptyString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = '';
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsNull(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = null;
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsEmptyArray(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = [];
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsZero(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = 0;
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsFalseIfInputIsZeroAsString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = '0';
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsTrueIfInputIsNonEmptyString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = 'hellö';
        self::assertTrue($validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function EmptyValidatorReturnsTrueIfInputIsNonEmptyArray(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = ['hellö'];
        self::assertTrue($validator->validate($input)->hasErrors());
    }
}
