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

namespace TYPO3Tests\TestJsonFields\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Example extends AbstractEntity
{
    protected string $title = '';
    protected string $description = '';
    protected string $tcaJsonField = '{}';
    protected string $nativeJsonAsTextField = '{}';

    public function getTcaJsonField(): string
    {
        return $this->tcaJsonField;
    }

    public function setTcaJsonField(string $tcaJsonField): void
    {
        $this->tcaJsonField = $tcaJsonField;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getNativeJsonAsTextField(): string
    {
        return $this->nativeJsonAsTextField;
    }

    public function setNativeJsonAsTextField(string $nativeJsonAsTextField): void
    {
        $this->nativeJsonAsTextField = $nativeJsonAsTextField;
    }
}
