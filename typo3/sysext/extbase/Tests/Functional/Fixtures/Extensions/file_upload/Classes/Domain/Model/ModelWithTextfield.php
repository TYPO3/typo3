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

namespace TYPO3Tests\FileUpload\Domain\Model;

use TYPO3\CMS\Extbase\Attribute\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ModelWithTextfield extends AbstractEntity
{
    #[Validate(validator: 'NotEmpty')]
    protected string $textfield = '';

    public function getTextfield(): string
    {
        return $this->textfield;
    }

    public function setTextfield(string $textfield): void
    {
        $this->textfield = $textfield;
    }
}
