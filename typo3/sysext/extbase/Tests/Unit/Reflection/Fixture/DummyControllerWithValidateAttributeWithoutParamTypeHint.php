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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

use TYPO3\CMS\Extbase\Attribute as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;

/**
 * Fixture class with #[Validate] attributes
 */
class DummyControllerWithValidateAttributeWithoutParamTypeHint extends ActionController
{
    #[Extbase\Validate(['param' => 'fooParam', 'validator' => NotEmptyValidator::class])]
    public function methodWithValidateAttributesAction($fooParam): void {}
}
