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

namespace ExtbaseTeam\BlogExample\Domain\Validator;

use ExtbaseTeam\BlogExample\Domain\Model\Post;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * A custom validator for blog posts.
 */
class PostValidator extends AbstractValidator
{

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param Post $value
     */
    protected function isValid($value)
    {
        if ($value->getTitle() === '77') {
            $error = new Error('Title custom validation failed', 1480872650);
            $this->result->forProperty('title')->addError($error);
        }
    }
}
