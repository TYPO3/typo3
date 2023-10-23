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

namespace TYPO3\CMS\Beuser\Domain\Dto;

/**
 * @internal not part of the TYPO3 Core API.
 */
class BackendUserGroup
{
    public function __construct(protected string $title = '') {}

    public static function fromUc(array $uc): self
    {
        $demand = new self();
        $demand->title = (string)($uc['title'] ?? '');
        return $demand;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function forUc(): array
    {
        return [
            'title' => $this->title,
        ];
    }
}
