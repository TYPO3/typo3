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
 * Representation of a backend route (and implicitly a module) that
 * shall be handled during the sudo mode process.
 *
 * @internal
 */
class RouteAccessSubject implements AccessSubjectInterface
{
    /**
     * The route subject, e.g. `/module/tools/maintenance`
     */
    protected string $subject;
    /**
     * The distinct lifetime type, e.g. XS, S, M, L, XL
     */
    protected AccessLifetime $lifetime;
    /**
     * If given, grants access to same-group sudo mode subjects.
     * Example: If access to admin tool route "maintenance" (of group "systemMaintainer")
     * was granted, access to other groups, like "settings" or "upgrade" are granted as well.
     */
    protected ?string $group;

    public static function fromArray(array $data): static
    {
        $subject = $data['subject'] ?? null;
        $lifetime = AccessLifetime::tryFrom($data['lifetime']);
        $group = $data['group'] ?? null;
        if (!is_string($subject)) {
            throw new \LogicException('Property subject must be of type string', 1681111813);
        }
        if ($lifetime === null) {
            throw new \LogicException('Property lifetime cannot be resolved', 1681111814);
        }
        if ($group !== null && !is_string($group)) {
            throw new \LogicException('Property group must be of type string, or omitted', 1681111815);
        }
        return new static($subject, $lifetime, $group);
    }

    final public function __construct(string $subject, AccessLifetime $lifetime = null, string $group = null)
    {
        $this->subject = $subject;
        $this->lifetime = $lifetime ?? AccessLifetime::veryShort;
        $this->group = $group;
    }

    public function jsonSerialize(): array
    {
        return [
            'class' => self::class,
            'subject' => $this->subject,
            'lifetime' => $this->lifetime->value,
            'group' => $this->group,
        ];
    }

    public function getIdentity(): string
    {
        return sprintf('route:%s', $this->subject);
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
}
