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

use TYPO3\CMS\Extbase\Attribute\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A blog post comment, restricted with specifically named hidden/starttime/endtime/fe_group attributes
 */
class RestrictedComment extends AbstractEntity
{
    #[Validate(['validator' => 'StringLength', 'options' => ['maximum' => 500]])]
    protected string $content = '';

    // Note: Simple string access, no model relation
    protected string $customfegroup = '';

    protected bool $customhidden = false;
    protected ?\DateTime $customstarttime = null;
    protected ?\DateTime $customendtime = null;

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCustomfegroup(): string
    {
        return $this->customfegroup;
    }

    public function setCustomfegroup(string $customfegroup): void
    {
        $this->customfegroup = $customfegroup;
    }

    public function getCustomhidden(): bool
    {
        return $this->customhidden;
    }

    public function setCustomhidden(bool $customhidden): void
    {
        $this->customhidden = $customhidden;
    }

    public function getCustomstarttime(): ?\DateTime
    {
        return $this->customstarttime;
    }

    public function setCustomstarttime(?\DateTime $customstarttime): void
    {
        $this->customstarttime = $customstarttime;
    }

    public function getCustomendtime(): ?\DateTime
    {
        return $this->customendtime;
    }

    public function setCustomendtime(?\DateTime $customendtime): void
    {
        $this->customendtime = $customendtime;
    }

}
