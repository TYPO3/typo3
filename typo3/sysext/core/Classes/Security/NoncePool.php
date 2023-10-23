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

namespace TYPO3\CMS\Core\Security;

/**
 * @internal
 */
class NoncePool implements SigningProviderInterface
{
    /**
     * maximum amount of items in pool
     */
    protected const DEFAULT_SIZE = 5;

    /**
     * items will expire after this amount of seconds
     */
    protected const DEFAULT_EXPIRATION = 900;

    /**
     * @var array{size: positive-int, expiration: int}
     */
    protected array $options;

    /**
     * @var array<string, Nonce>
     */
    protected array $items;

    /**
     * @var array<string, ?Nonce>
     */
    protected array $changeItems = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $nonces = [], array $options = [])
    {
        $this->options = [
            'size' => max(1, (int)($options['size'] ?? self::DEFAULT_SIZE)),
            'expiration' => max(0, (int)($options['expiration'] ?? self::DEFAULT_EXPIRATION)),
        ];

        foreach ($nonces as $name => $value) {
            if ($value !== null && !$value instanceof Nonce) {
                throw new \LogicException(sprintf('Invalid valid for nonce "%s"', $name), 1664195013);
            }
        }
        // filter valid items
        $this->items = array_filter(
            $nonces,
            fn(?Nonce $item, string $name) => $item !== null
                && $this->isValidNonceName($item, $name)
                && $this->isNonceUpToDate($item),
            ARRAY_FILTER_USE_BOTH
        );
        // items that were not valid -> to be revoked
        $invalidItems = array_diff_key($nonces, $this->items);
        $this->changeItems = array_fill_keys(array_keys($invalidItems), null);
    }

    public function findSigningSecret(string $name): ?Nonce
    {
        return $this->items[$name] ?? null;
    }

    public function provideSigningSecret(): Nonce
    {
        $items = array_filter($this->changeItems);
        $nonce = reset($items);
        if (!$nonce instanceof Nonce) {
            $nonce = Nonce::create();
            $this->emit($nonce);
        }
        return $nonce;
    }

    public function merge(self $other): self
    {
        $this->items = array_merge($this->items, $other->items);
        $this->changeItems = array_merge($this->changeItems, $other->changeItems);
        return $this;
    }

    public function purge(): self
    {
        $size = $this->options['size'];
        $items = array_filter($this->items);
        if (count($items) <= $size) {
            return $this;
        }
        uasort($items, static fn(Nonce $a, Nonce $b) => $b->time <=> $a->time);
        $exceedingItems = array_splice($items, $size, null, []);
        foreach ($exceedingItems as $name => $_) {
            $this->changeItems[$name] = null;
        }
        return $this;
    }

    public function emit(Nonce $nonce): self
    {
        $this->changeItems[$nonce->getSigningIdentifier()->name] = $nonce;
        return $this;
    }

    public function revoke(Nonce $nonce): self
    {
        $this->revokeSigningSecret($nonce->getSigningIdentifier()->name);
        return $this;
    }

    public function revokeSigningSecret(string $name): void
    {
        if (isset($this->items[$name])) {
            $this->changeItems[$name] = null;
        }
    }

    /**
     * @return array<string, Nonce>
     */
    public function getEmittableNonces(): array
    {
        return array_filter($this->changeItems);
    }

    /**
     * @return list<string>
     */
    public function getRevocableNames(): array
    {
        return array_keys(
            array_diff_key($this->changeItems, $this->getEmittableNonces())
        );
    }

    protected function isValidNonceName(Nonce $nonce, $name): bool
    {
        return $nonce->getSigningIdentifier()->name === $name;
    }

    protected function isNonceUpToDate(Nonce $nonce): bool
    {
        if ($this->options['expiration'] <= 0) {
            return true;
        }
        $now = new \DateTimeImmutable();
        $interval = new \DateInterval(sprintf('PT%dS', $this->options['expiration']));
        return $nonce->time->add($interval) > $now;
    }
}
