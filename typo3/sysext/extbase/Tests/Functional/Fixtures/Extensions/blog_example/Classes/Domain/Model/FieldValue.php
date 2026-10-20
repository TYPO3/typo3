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

/**
 * A record with one field per TCA field option that DataHandler normalises or validates
 */
class FieldValue extends AbstractEntity
{
    protected string $title = '';
    protected string $trimmed = '';
    protected string $limited = '';
    protected string $summary = '';
    protected string $serial = '0';
    protected int $quantity = 0;
    protected string $mandatory = '';
    protected string $code = '';
    protected string $reference = '';
    protected string $contact = '';
    protected ?\DateTimeImmutable $published = null;
    protected string $secret = '';
    protected string $slug = '';
    protected string $siteSlug = '';
    protected string $label = '';
    protected int $amount = 0;
    protected bool $flag = false;
    protected ?string $note = null;
    protected ?\DateTimeImmutable $dueDate = null;
    protected ?\DateTimeImmutable $dueMoment = null;
    protected ?\DateTimeImmutable $dueDay = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setTrimmed(string $trimmed): void
    {
        $this->trimmed = $trimmed;
    }

    public function setLimited(string $limited): void
    {
        $this->limited = $limited;
    }

    public function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    public function setSerial(string $serial): void
    {
        $this->serial = $serial;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setMandatory(string $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function setContact(string $contact): void
    {
        $this->contact = $contact;
    }

    public function setPublished(?\DateTimeImmutable $published): void
    {
        $this->published = $published;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function setSiteSlug(string $siteSlug): void
    {
        $this->siteSlug = $siteSlug;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function setDueMoment(?\DateTimeImmutable $dueMoment): void
    {
        $this->dueMoment = $dueMoment;
    }

    public function setDueDay(?\DateTimeImmutable $dueDay): void
    {
        $this->dueDay = $dueDay;
    }
}
