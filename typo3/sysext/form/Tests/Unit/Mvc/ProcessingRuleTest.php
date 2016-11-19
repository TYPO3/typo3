<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Tests\Unit\Mvc\Validation\Fixtures\TestValidator;

/**
 * Test case
 */
class ProcessingRuleTest extends UnitTestCase
{

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

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
        $this->assertInstanceOf(AbstractValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function processNoPropertyMappingReturnsNotModifiedValue()
    {
        $objectMangerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectMangerProphecy->reveal());
        $resultProphecy = $this->prophesize(Result::class);

        $objectMangerProphecy
            ->get(Result::class)
            ->willReturn($resultProphecy->reveal());

        $mockProcessingRule = $this->getAccessibleMock(ProcessingRule::class, [
            'dummy'
        ], [], '', false);

        $mockProcessingRule->_set('dataType', null);
        $mockProcessingRule->_set('processingMessages', $resultProphecy->reveal());
        $mockProcessingRule->_set('validator', new ConjunctionValidator([]));

        $input = 'someValue';
        $this->assertSame($input, $mockProcessingRule->_call('process', $input));
    }

    /**
     * @test
     */
    public function processNoPropertyMappingAndHasErrorsIfValidatorContainsErrors()
    {
        $objectMangerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectMangerProphecy->reveal());

        $objectMangerProphecy
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

        $this->assertTrue($mockProcessingRule->_get('processingMessages')->hasErrors());
    }
}
