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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representation of a specific application type scope (backend, frontend),
 * which can optionally be enriched by site-related details.
 */
final class Scope implements \Stringable, \JsonSerializable
{
    /**
     * @var array<string, self>
     */
    private static array $singletons = [];

    /**
     * @deprecated actually just `@internal` - but it might be removed later
     */
    public readonly ?Site $site;

    public static function backend(): self
    {
        return self::asSingleton(new self(ApplicationType::BACKEND));
    }

    public static function frontend(): self
    {
        return self::asSingleton(new self(ApplicationType::FRONTEND));
    }

    /**
     * @internal might be removed later
     */
    public static function frontendSite(?SiteInterface $site): self
    {
        // PHPStan fails, see https://github.com/phpstan/phpstan/issues/8464
        // @phpstan-ignore-next-line
        if (!$site instanceof Site || is_subclass_of($site, Site::class)) {
            return self::frontend();
        }
        return self::asSingleton(new self(ApplicationType::FRONTEND, $site->getIdentifier(), $site));
    }

    public static function frontendSiteIdentifier(string $siteIdentifier): self
    {
        return self::asSingleton(new self(ApplicationType::FRONTEND, $siteIdentifier));
    }

    public static function from(string $value): self
    {
        $parts = GeneralUtility::trimExplode('.', $value, true);
        $type = ApplicationType::tryFrom($parts[0] ?? '');
        $siteIdentifier = $parts[1] ?? null;
        if ($type === null) {
            throw new \LogicException(
                sprintf('Could not resolve application type from "%s"', $value),
                1677424928
            );
        }
        return self::asSingleton(new self($type, $siteIdentifier));
    }

    public static function reset(): void
    {
        self::$singletons = [];
    }

    public static function tryFrom(string $value): ?self
    {
        try {
            return self::from($value);
        } catch (\LogicException) {
            return null;
        }
    }

    private static function asSingleton(self $self): self
    {
        $id = (string)$self;
        if (!isset(self::$singletons[$id])) {
            self::$singletons[$id] = $self;
        }
        return self::$singletons[$id];
    }

    /**
     * Use static functions to create singleton instances.
     */
    private function __construct(
        public readonly ApplicationType $type,
        public readonly ?string $siteIdentifier = null,
        ?Site $site = null,
    ) {
        $this->site = $site;
    }

    public function __toString(): string
    {
        $value = $this->type->value;
        if ($this->siteIdentifier !== null) {
            $value .= '.' . $this->siteIdentifier;
        }
        return $value;
    }

    public function isFrontendSite(): bool
    {
        return $this->siteIdentifier !== null && $this->type->isFrontend();
    }

    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
