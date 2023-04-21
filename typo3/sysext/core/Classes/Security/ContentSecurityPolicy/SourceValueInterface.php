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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Interface used for self-contained source value models.
 * The parent `SourceInterface` is basically just a type interface, since
 * type cannot be declared better in PHP. This `SourceValueInterface` is
 * focussed on real class instances, but not on `enum` objects.
 *
 * @internal This implementation might still change
 */
interface SourceValueInterface extends SourceInterface
{
    /**
     * Determines whether a serialized representation is known and can be handled
     * by a specific implementation, (e.g. "string starts with 'hash-proxy-").
     */
    public static function knows(string $value): bool;

    /**
     * Parses a known serialized representation as object representation.
     */
    public static function parse(string $value): self;

    /**
     * Compiled representation to be used for Content-Security-Policy HTTP header.
     * @return ?string `null` means "not applicable / skip"
     */
    public function compile(?FrontendInterface $cache = null): ?string;

    /**
     * Serialized representation to be used for persisting declaration (e.g. in database).
     * @return ?string `null` means "not applicable / skip"
     */
    public function serialize(): ?string;
}
