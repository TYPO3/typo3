<?php

declare(strict_types=1);

namespace T3docs\Examples\Reaction;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

class ExampleReactionType implements ReactionInterface
{
    private const REGISTRY_KEY = 'changed_ids';

    public function __construct(
        private readonly Registry $registry,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public static function getType(): string
    {
        return 'example-reaction-type';
    }

    public static function getDescription(): string
    {
        return 'Example reaction type';
    }

    public static function getIconIdentifier(): string
    {
        return 'tx_examples-dummy';
    }

    public function react(
        ServerRequestInterface $request,
        array $payload,
        ReactionInstruction $reaction
    ): ResponseInterface {
        $id = $payload['id'] ?? 0;
        if ($id <= 0) {
            $data = [
                'success' => false,
                'error' => 'id not given',
            ];

            return $this->jsonResponse($data, 400);
        }

        $this->updateRegistryEntry($id);

        return $this->jsonResponse(['success' => true]);
    }

    private function updateRegistryEntry(int $id): void
    {
        $ids = $this->registry->get('tx_examples', self::REGISTRY_KEY) ?? [];
        $ids[] = $id;
        $ids = array_unique($ids);
        $this->registry->set('tx_examples', self::REGISTRY_KEY, $ids);
    }

    private function jsonResponse(array $data, int $statusCode = 201): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($data, JSON_THROW_ON_ERROR)));
    }
}
