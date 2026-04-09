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

namespace TYPO3Tests\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class TtContentWithCType extends AbstractEntity
{
    protected string $header = '';

    protected string $ctype = '';

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function setCtype(string $ctype): void
    {
        $this->ctype = $ctype;
    }
}
