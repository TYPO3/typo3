<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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
 * Test case for the Abstract Validator
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
 */
abstract class AbstractValidatorTestcase extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function setUp()
    {
        $this->validator = $this->getValidator();
    }

    /**
     * @param array $options
     * @return mixed
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function getValidator($options = [])
    {
        trigger_error(
            __CLASS__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        $validator = new $this->validatorClassName($options);
        return $validator;
    }

    /**
     * @param array $options
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function validatorOptions($options)
    {
        trigger_error(
            __CLASS__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        $this->validator = $this->getValidator($options);
    }
}
