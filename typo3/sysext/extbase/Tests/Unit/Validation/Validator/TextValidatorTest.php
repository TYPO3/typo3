<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\TextValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the text validator
 */
class TextValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = TextValidator::class;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * @test
     */
    public function textValidatorReturnsNoErrorForASimpleString()
    {
        self::assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorAllowsTheNewLineCharacter()
    {
        $sampleText = 'Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. nVu dan durch jéngt gréng, ze rou Monn voll stolz. nKe kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d\'Gaassen eng, eng am virun geplot d\'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d\'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d\'wäiss mat wa.';
        self::assertFalse($this->validator->validate($sampleText)->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorAllowsCommonSpecialCharacters()
    {
        $sampleText = '3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.';
        self::assertFalse($this->validator->validate($sampleText)->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorReturnsErrorForAStringWithHtml()
    {
        self::assertTrue($this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities()
    {
        // we only test for the error code, after the translation Method for message is mocked anyway
        $expected = [new Error('', 1221565786)];
        self::assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
    }
}
