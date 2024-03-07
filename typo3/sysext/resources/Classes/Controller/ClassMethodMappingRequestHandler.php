<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Controller;

use TYPO3\CMS\Resources\Message\Method;
use TYPO3\CMS\Resources\Message\Numerus;
use TYPO3\CMS\Resources\Message\ResourceRequestHandlerInterface;
use TYPO3\CMS\Resources\Message\ResourceRequestInterface;
use TYPO3\CMS\Resources\Message\ResourceResponse;
use TYPO3\CMS\Resources\Message\ResourceResponseInterface;
use TYPO3\CMS\Resources\Message\Status;
use TYPO3\CMS\Resources\Routing\ResourceRouteResult;
use Webmozart\Assert\Assert;

final class ClassMethodMappingRequestHandler implements ResourceRequestHandlerInterface
{

    public function __construct(
        private ResourceController $controller
    )
    {}

    public function handle(ResourceRequestInterface $request): ResourceResponseInterface
    {
        /** @var ResourceRouteResult $resourceRoute */
        $resourceRoute = $request->getAttribute('route');
        Assert::isInstanceOf($resourceRoute, ResourceRouteResult::class);

        $controllerCall = $this->mapRequestMethodToControllerCall($resourceRoute->getResourceRouteOptions()->getNumerus(), $request->getMethodObject());

        return $controllerCall(...$resourceRoute->getArguments());
    }

    private function mapRequestMethodToControllerCall(Numerus $numerus, Method $method): \Closure
    {
        $controllerMethodName = match ($numerus) {
            Numerus::Item => match ($method) {
                Method::Create => 'create',
                Method::Read => 'get',
                Method::Replace => 'replace',
                Method::Update => 'update',
                Method::Delete => 'delete',
                default => null
            },
            Numerus::Collection => match ($method) {
                Method::Create => 'create',
                Method::Read => 'list',
                Method::Delete => 'clear',
                default => null
            },
        };

        if ($controllerMethodName) {
            $controllerMethodName = method_exists($this->controller, $controllerMethodName) ? $controllerMethodName : null;
        }

        if (!$controllerMethodName) {
            return $this->unsupportedMethod(...);
        }

        return $this->controller->{$controllerMethodName}(...);
    }

    private function unsupportedMethod(?string $id = null): ResourceResponseInterface
    {
        return new ResourceResponse(Status::NotImplemented);
    }
}
