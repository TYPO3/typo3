<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Repository;

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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Demand Object for filtering redirects in the backend module
 * @internal
 */
class Demand
{
    /**
     * @var string
     */
    protected $sourceHost;

    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var int
     */
    protected $limit = 50;

    /**
     * @var int
     */
    protected $page;

    /**
     * Demand constructor.
     * @param int $page
     * @param string $sourceHost
     * @param string $sourcePath
     * @param string $target
     * @param int $statusCode
     */
    public function __construct(int $page = 1, string $sourceHost = '', string $sourcePath = '', string $target = '', int $statusCode = 0)
    {
        $this->page = $page;
        $this->sourceHost = $sourceHost;
        $this->sourcePath = $sourcePath;
        $this->target = $target;
        $this->statusCode = $statusCode;
    }

    /**
     * Creates a Demand object from the current request.
     *
     * @param ServerRequestInterface $request
     * @return Demand
     */
    public static function createFromRequest(ServerRequestInterface $request): Demand
    {
        $page = (int)($request->getQueryParams()['page'] ?? $request->getParsedBody()['page'] ?? 1);
        $demand = $request->getQueryParams()['demand'] ?? $request->getParsedBody()['demand'];
        if (empty($demand)) {
            return new self($page);
        }
        $sourceHost = $demand['source_host'] ?? '';
        $sourcePath = $demand['source_path'] ?? '';
        $statusCode = (int)($demand['target_statuscode'] ?? 0);
        $target = $demand['target'] ?? '';
        return new self($page, $sourceHost, $sourcePath, $target, $statusCode);
    }

    /**
     * @return string
     */
    public function getSourceHost(): string
    {
        return $this->sourceHost;
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function hasSourceHost(): bool
    {
        return $this->sourceHost !== '';
    }

    /**
     * @return bool
     */
    public function hasSourcePath(): bool
    {
        return $this->sourcePath !== '';
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool
    {
        return $this->target !== '';
    }

    /**
     * @return bool
     */
    public function hasStatusCode(): bool
    {
        return $this->statusCode !== 0;
    }

    /**
     * @return bool
     */
    public function hasConstraints(): bool
    {
        return $this->hasSourcePath()
            || $this->hasSourceHost()
            || $this->hasTarget();
    }

    /**
     * The current Page of the paginated redirects
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Offset for the current set of records
     *
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        $parameters = [];
        if ($this->hasSourcePath()) {
            $parameters['source_path'] = $this->sourcePath;
        }
        if ($this->hasSourceHost()) {
            $parameters['source_host'] = $this->sourceHost;
        }
        if ($this->hasTarget()) {
            $parameters['target'] = $this->target;
        }
        if ($this->hasStatusCode()) {
            $parameters['target_statuscode'] = $this->target;
        }
        return $parameters;
    }
}
