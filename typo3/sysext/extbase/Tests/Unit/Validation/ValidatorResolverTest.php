<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ValidatorResolverTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver | \PHPUnit_Framework_MockObject_MockObject | \TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $validatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    protected function setUp()
    {
        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['dummy']);
    }

    /**
     * @test
     */
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        static::assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'integer'));
        static::assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'int'));
        static::assertEquals('String', $this->validatorResolver->_call('getValidatorType', 'string'));
        static::assertEquals('Array', $this->validatorResolver->_call('getValidatorType', 'array'));
        static::assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'float'));
        static::assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'double'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'boolean'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        static::assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'number'));
        static::assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'numeric'));
    }

    /**
     * @test
     */
    public function getValidatorTypeRenamesMixedToRaw()
    {
        static::assertEquals('Raw', $this->validatorResolver->_call('getValidatorType', 'mixed'));
    }
}
