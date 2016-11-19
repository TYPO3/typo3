<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation\Fixtures;

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

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator for unit tetst
 *
 * @api
 */
class TestValidator extends AbstractValidator
{
    /**
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if ($value === 'addError') {
            $this->addError('Error', 1480201569);
        }
    }
}
