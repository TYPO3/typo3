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
 * Note: This class is excluded from phpstan analysing, because of errors which are test-purpose related.
 */
class DummyController extends ActionController
{
    /**
     * @param $fooParam
     */
    public function methodWithoutValidateAttributesAction($fooParam): void {}

    #[Extbase\Validate(['param' => 'fooParam', 'validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 10]])]
    #[Extbase\Validate(['param' => 'fooParam', 'validator' => 'NotEmpty'])]
    #[Extbase\Validate(['param' => 'fooParam', 'validator' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator'])]
    #[Extbase\Validate(['param' => 'fooParam', 'validator' => NotEmptyValidator::class])]
    public function methodWithValidateAttributesAction(string $fooParam): void {}
}
