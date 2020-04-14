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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture model
 */
class Model extends AbstractEntity
{
    /**
     * @var string
     * @Extbase\Validate("StringLength", options={"minimum": 1})
     * @Extbase\Validate("StringLength", options={"maximum": 10})
     * @Extbase\Validate("NotEmpty")
     */
    protected $foo;

    /**
     * @var int
     * @Extbase\Validate("\TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Validation\Validator\CustomValidator")
     */
    protected $bar;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    protected $baz;

    /**
     * @var \TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\AnotherModel
     */
    protected $qux;
}
