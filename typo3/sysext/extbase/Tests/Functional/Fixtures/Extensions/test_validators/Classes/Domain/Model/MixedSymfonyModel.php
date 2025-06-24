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

use Symfony\Component\Validator\Constraints as Assert;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture model
 */
class MixedSymfonyModel extends AbstractEntity
{
    #[Assert\Length(
        min: 2,
        minMessage: 'Your foo must be at least {{ limit }} characters long',
    )]
    #[Validate(['validator' => 'NotEmpty'])]
    #[Validate(['validator' => 'StringLength', 'options' => ['maximum' => 10]])]
    protected string $foo;

}
