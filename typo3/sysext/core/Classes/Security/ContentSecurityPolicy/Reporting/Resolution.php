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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ModelService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class Resolution implements \JsonSerializable
{
    public readonly \DateTimeImmutable $created;

    public static function fromArray(array $array): static
    {
        if (!isset($array['summary'])) {
            throw new \LogicException('Summary must be given', 1677263951);
        }
        $service = GeneralUtility::makeInstance(ModelService::class);
        $mutationCollection = $array['mutation_collection'] ?? null;
        if (is_string($mutationCollection)) {
            $mutationCollection = json_decode($mutationCollection, true, 16, JSON_THROW_ON_ERROR);
        }
        $mutationCollection = $service->buildMutationCollectionFromArray(
            is_array($mutationCollection) ? $mutationCollection : []
        );
        $meta = json_decode($array['meta'] ?? '', true, 16, JSON_THROW_ON_ERROR);
        return new static(
            $array['summary'],
            Scope::from($array['scope'] ?? ''),
            $array['mutation_identifier'],
            $mutationCollection,
            $meta ?: [],
            new \DateTimeImmutable('@' . ($array['created'] ?? '0')),
        );
    }

    final public function __construct(
        public readonly string $summary,
        public readonly Scope $scope,
        public readonly string $mutationIdentifier,
        public readonly MutationCollection $mutationCollection,
        public readonly array $meta = [],
        \DateTimeImmutable $created = null,
    ) {
        $this->created = $created ?? new \DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'summary' => $this->summary,
            'created' => $this->created->format(\DateTimeInterface::ATOM),
            'scope' => $this->scope,
            'mutationIdentifier' => $this->mutationIdentifier,
            'mutationCollection' => $this->mutationCollection,
            'meta' => $this->meta,
        ];
    }

    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'created' => $this->created->getTimestamp(),
            'scope' => (string)$this->scope,
            'mutation_identifier' => $this->mutationIdentifier,
            'mutation_collection' => json_encode($this->mutationCollection),
            'meta' => json_encode($this->meta),
        ];
    }
}
