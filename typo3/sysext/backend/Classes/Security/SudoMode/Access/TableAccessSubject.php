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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

/**
 * Representation of a table or table column that
 * shall be handled during the sudo mode process.
 *
 * @internal
 */
class TableAccessSubject implements AccessSubjectInterface
{
    /**
     * The table column subject, e.g. `tx_foo`, `tx_foo.bar` or `tx_foo.bar.123`
     */
    protected string $subject;

    /**
     * The distinct lifetime type, e.g. XS, S, M, L, XL
     */
    protected AccessLifetime $lifetime;

    /**
     * If given, grants access to same-group sudo mode subjects.
     */
    protected ?string $group;

    /**
     * If true, the subject may only be used once and requires a new grant for the same task.
     */
    protected bool $once;

    public static function fromArray(array $data): static
    {
        $subject = $data['subject'] ?? null;
        $lifetime = AccessLifetime::tryFrom($data['lifetime']);
        $group = $data['group'] ?? null;
        $once = $data['once'] ?? null;
        if (!is_string($subject)) {
            throw new \LogicException('Property subject must be of type string', 1743646793);
        }
        if ($lifetime === null) {
            throw new \LogicException('Property lifetime cannot be resolved', 1743646794);
        }
        if ($group !== null && !is_string($group)) {
            throw new \LogicException('Property group must be of type string, or omitted', 1743646795);
        }
        if ($once !== null && !is_bool($once)) {
            throw new \LogicException('Property once must be of type bool, or omitted', 1743646796);
        }
        return new static($subject, $lifetime, $group, $once);
    }

    final public function __construct(
        string $subject,
        ?AccessLifetime $lifetime = null,
        ?string $group = null,
        ?bool $once = null,
    ) {
        $this->subject = $subject;
        $this->lifetime = $lifetime ?? AccessLifetime::veryShort;
        $this->group = $group;
        $this->once = $once;
    }

    public function jsonSerialize(): array
    {
        return [
            'class' => self::class,
            'identity' => $this->getIdentity(),
            'subject' => $this->subject,
            'lifetime' => $this->lifetime->value,
            'group' => $this->group,
            'once' => $this->once,
        ];
    }

    public function getIdentity(): string
    {
        return sprintf('table:%s', $this->subject);
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getLifetime(): AccessLifetime
    {
        return $this->lifetime;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }
}
