<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Controller;

use TYPO3\CMS\Resources\Message\ResourceRequestHandlerInterface;
use TYPO3\CMS\Resources\Message\ResourceRequestInterface;
use TYPO3\CMS\Resources\Message\ResourceResponse;
use TYPO3\CMS\Resources\Message\ResourceResponseInterface;
use TYPO3\CMS\Resources\Message\Status;
use TYPO3\CMS\Resources\RepositoryInterface;

final class ResourceController implements ResourceRequestHandlerInterface
{
    private ClassMethodMappingRequestHandler $requestHandler;

    public function __construct(private RepositoryInterface $repository)
    {
        $this->requestHandler = new ClassMethodMappingRequestHandler($this);
    }

    public function handle(ResourceRequestInterface $request): ResourceResponseInterface
    {
        return $this->requestHandler->handle($request);
    }

    public function get(string $id): ResourceResponse
    {
        $item = $this->repository->findById($id);
        return new ResourceResponse(Status::OK, $item);
    }

    public function list(): ResourceResponse
    {
        $items = $this->repository->findAll();
        return new ResourceResponse(Status::OK, $items);
    }
}
