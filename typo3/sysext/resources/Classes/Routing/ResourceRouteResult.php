<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing;

readonly class ResourceRouteResult
{
    public function __construct(
        protected string $routeName,
        protected ResourceRouteOptionsInterface $resourceRouteOptions,
        protected array $arguments = [],
    ) {}

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getResourceRouteOptions(): ResourceRouteOptionsInterface
    {
        return $this->resourceRouteOptions;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
