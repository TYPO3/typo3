<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Tests\Unit\Mvc\Validation\Fixtures\TestValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ProcessingRuleTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function addValidatorAddValidator()
    {
        $mockProcessingRule = $this->getAccessibleMock(ProcessingRule::class, [
            'dummy'
        ], [], '', false);

        $mockProcessingRule->_set('validator', new ConjunctionValidator([]));
        $mockProcessingRule->addValidator(new TestValidator());
        $validators = $mockProcessingRule->_get('validator')->getValidators();
        $validators->rewind();
        self::assertInstanceOf(AbstractValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function processNoPropertyMappingReturnsNotModifiedValue()
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());
        $resultProphecy = $this->prophesize(Result::class);

        $objectManagerProphecy
            ->get(Result::class)
            ->willReturn($resultProphecy->reveal());

        $mockProcessingRule = $this->getAccessibleMock(ProcessingRule::class, [
            'dummy'
        ], [], '', false);

        $mockProcessingRule->_set('dataType', null);
        $mockProcessingRule->_set('processingMessages', $resultProphecy->reveal());
        $mockProcessingRule->_set('validator', new ConjunctionValidator([]));

        $input = 'someValue';
        self::assertSame($input, $mockProcessingRule->_call('process', $input));
    }

    /**
     * @test
     */
    public function processNoPropertyMappingAndHasErrorsIfValidatorContainsErrors()
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $objectManagerProphecy
            ->get(Result::class)
            ->willReturn(new Result);

        $mockProcessingRule = $this->getAccessibleMock(ProcessingRule::class, [
            'dummy'
        ], [], '', true);

        $mockProcessingRule->_set('dataType', null);
        $mockProcessingRule->_set('validator', new ConjunctionValidator([]));
        $mockProcessingRule->addValidator(new TestValidator());

        $input = 'addError';
        $mockProcessingRule->_call('process', $input);

        self::assertTrue($mockProcessingRule->_get('processingMessages')->hasErrors());
    }
}
