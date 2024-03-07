<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

interface ResourceRequestHandlerInterface
{
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ResourceRequestInterface $request): ResourceResponseInterface;
}
