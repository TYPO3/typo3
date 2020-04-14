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

namespace TYPO3\CMS\Core\DataHandling\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CorrelationId representation
 *
 * @todo Check internal state during v10 development
 * @internal
 */
class CorrelationId implements \JsonSerializable
{
    protected const DEFAULT_VERSION = 1;
    protected const PATTERN_V1 = '#^(?P<flags>[[:xdigit:]]{4})\$(?:(?P<scope>[[:alnum:]]+):)?(?P<subject>[[:alnum:]]+)(?P<aspects>(?:\/[[:alnum:]._-]+)*)$#';

    /**
     * @var int
     */
    protected $version = self::DEFAULT_VERSION;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var int
     */
    protected $capabilities = 0;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string[]
     */
    protected $aspects = [];

    /**
     * @param string $scope
     * @return static
     */
    public static function forScope(string $scope): self
    {
        $target = static::create();
        $target->scope = $scope;
        return $target;
    }

    public static function forSubject(string $subject, string ...$aspects): self
    {
        return static::create()
            ->withSubject($subject)
            ->withAspects(...$aspects);
    }

    /**
     * @param string $correlationId
     * @return static
     */
    public static function fromString(string $correlationId): self
    {
        if (!preg_match(self::PATTERN_V1, $correlationId, $matches, PREG_UNMATCHED_AS_NULL)) {
            throw new \InvalidArgumentException('Unknown format', 1569620858);
        }

        $flags = hexdec($matches['flags'] ?? 0);
        $aspects = !empty($matches['aspects']) ? explode('/', ltrim($matches['aspects'] ?? '', '/')) : [];
        $target = static::create()
            ->withSubject($matches['subject'])
            ->withAspects(...$aspects);
        $target->scope = $matches['scope'] ?? null;
        $target->version = $flags >> 10;
        $target->capabilities = $flags & ((1 << 10) - 1);
        return  $target;
    }

    /**
     * @return static
     */
    protected static function create(): self
    {
        return GeneralUtility::makeInstance(static::class);
    }

    public function __toString(): string
    {
        if ($this->subject === null) {
            throw new \LogicException('Cannot serialize for empty subject', 1569668681);
        }
        return $this->serialize();
    }

    public function jsonSerialize(): string
    {
        return (string)$this;
    }

    public function withSubject(string $subject): self
    {
        if ($this->subject === $subject) {
            return $this;
        }
        $target = clone $this;
        $target->subject = $subject;
        return $target;
    }

    public function withAspects(string ...$aspects): self
    {
        if ($this->aspects === $aspects) {
            return $this;
        }
        $target = clone $this;
        $target->aspects = $aspects;
        return $target;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return string[]
     */
    public function getAspects(): array
    {
        return $this->aspects;
    }

    /**
     * v1 specs (eBNF)
     * + FLAGS "$" [ SCOPE ":" ] SUBJECT { "/" ASPECT }
     *   + FLAGS   ::= XDIGIT (* 16-bit integer big-endian)
     *   + SCOPE   ::= ALNUM { ALNUM }
     *   + SUBJECT ::= ALNUM { ALNUM }
     *   + ASPECT  ::= ( ALNUM | '.' | '_' | '-' ) { ( ALNUM | '.' | '_' | '-' ) }
     */
    protected function serialize(): string
    {
        // 6-bit version 10-bit capabilities
        $flags = $this->version << 10 + $this->capabilities;
        return sprintf(
            '%s$%s%s%s',
            bin2hex(pack('n', $flags)),
            $this->scope ? $this->scope . ':' : '',
            $this->subject,
            $this->aspects ? '/' . implode('/', $this->aspects) : ''
        );
    }
}
