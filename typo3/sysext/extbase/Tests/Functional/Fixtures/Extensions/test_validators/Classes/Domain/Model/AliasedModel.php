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

namespace TYPO3Tests\TestValidators\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture model
 * I am here, to remind you,
 * of the Annotations that where here
 * before you went away
 * it's not fair, to deny me
 * of validators that I bear
 * that you gave to me
 * you, you, you oughta test,
 * while I'm here
 */
class AliasedModel extends AbstractEntity
{
    /**
     * @var string
     */
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1]])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 10]])]
    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    protected $foo;
}
