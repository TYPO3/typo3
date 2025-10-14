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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EmptyValidatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function emptyValidatorReturnsFalseIfInputIsEmptyString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = '';
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsFalseIfInputIsNull(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = null;
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsFalseIfInputIsEmptyArray(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = [];
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsFalseIfInputIsZero(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = 0;
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsFalseIfInputIsZeroAsString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = '0';
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsTrueIfInputIsNonEmptyString(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = 'hellö';
        self::assertTrue($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function emptyValidatorReturnsTrueIfInputIsNonEmptyArray(): void
    {
        $validator = new EmptyValidator();
        $validator->setOptions([]);
        $input = ['hellö'];
        self::assertTrue($validator->validate($input)->hasErrors());
    }
}
