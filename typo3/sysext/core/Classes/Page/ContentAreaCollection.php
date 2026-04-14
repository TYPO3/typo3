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

namespace TYPO3\CMS\Core\Page;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Collection of content areas
 *
 * @internal
 *
 * @implements \IteratorAggregate<string, ContentArea>
 */
final readonly class ContentAreaCollection implements ContainerInterface, \IteratorAggregate
{
    public function __construct(
        /** @var ContentAreaClosure[]|ContentArea[] $contentAreas */
        private array $contentAreas,
        private ?ServerRequestInterface $request = null,
    ) {}

    public function withRequest(ServerRequestInterface $request): self
    {
        return new self($this->contentAreas, $request);
    }

    public function get(string $id): ContentArea
    {
        if (!$this->has($id)) {
            throw new ContentAreaNotFoundException('No content area found for identifier: ' . $id, 1726479567);
        }
        $area = $this->contentAreas[$id];
        if ($area instanceof ContentAreaClosure) {
            if ($this->request === null) {
                throw new \LogicException('Cannot instantiate ContentAreaClosure without a request. Call withRequest() first.', 1776158770);
            }
            return $area->instantiate($this->request);
        }
        return $area;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->contentAreas);
    }

    /**
     * @internal Only for AfterContentHasBeenFetchedEvent
     */
    public function getGroupedRecords(ServerRequestInterface $request): array
    {
        $areas = [];
        foreach ($this->contentAreas as $area) {
            $area = $area instanceof ContentAreaClosure ? $area->instantiate($request) : $area;
            $areas[$area->getIdentifier()] = [
                'name' => $area->getName(),
                'colPos' => $area->getColPos(),
                'identifier' => $area->getIdentifier(),
                'allowedContentTypes' => $area->getAllowedContentTypes(),
                'records' => $area->getRecords(),
                'area' => $area,
            ];
        }
        return $areas;
    }

    /**
     * @internal Only for AfterContentHasBeenFetchedEvent
     */
    public function withUpdatedRecords(array $groupedRecords): self
    {
        $areas = [];
        foreach ($groupedRecords as $identifier => $data) {
            $areas[$identifier] = $data['area']->withRecords($data['records']);
        }
        return new self($areas);
    }

    /**
     * @return \Traversable<string, ContentArea>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->contentAreas);
    }
}
