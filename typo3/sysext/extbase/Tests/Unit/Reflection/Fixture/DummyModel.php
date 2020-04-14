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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture model
 */
class DummyModel extends AbstractEntity
{
    protected $propertyWithoutValidateAnnotations;

    /**
     * @Extbase\Validate("StringLength", options={"minimum": 1, "maximum": 10})
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("TYPO3.CMS.Extbase:NotEmpty")
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator")
     * @Extbase\Validate("\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     * @Extbase\Validate("TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected $propertyWithValidateAnnotations;
}
