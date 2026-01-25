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
 * Fixture model with a transient property typed as another model without a getter.
 * This is used to test that transient properties without accessors don't cause
 * validation exceptions.
 */
class ModelWithTransientProperty extends AbstractEntity
{
    protected string $title = '';

    /**
     * A transient property typed as another model, without a public getter.
     * This should be skipped during validation.
     */
    #[Extbase\ORM\Transient]
    protected ?AnotherModel $transientRelation = null;

    /**
     * @Extbase\ORM\Transient
     */
    protected ?AnotherModel $anotherTransientRelation = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setTransientRelation(?AnotherModel $transientRelation): void
    {
        $this->transientRelation = $transientRelation;
    }
}
