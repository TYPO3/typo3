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

namespace TYPO3\CMS\Webhooks\MessageHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3\CMS\Webhooks\Model\WebhookInstruction;
use TYPO3\CMS\Webhooks\Repository\WebhookRepository;

/**
 * A Message Handler to deal with a webhook message.
 * It sends the message to all registered HTTP endpoints.
 */
class WebhookMessageHandler
{
    private string $algo = 'sha256';

    public function __construct(
        private readonly WebhookRepository $repository,
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(WebhookMessageInterface $message): void
    {
        $configuredWebhooks = $this->repository->getConfiguredWebhooksByType(get_class($message));
        foreach ($configuredWebhooks as $webhookInstruction) {
            $this->logger->info('Sending webhook', [
                'webhook-identifier' => $webhookInstruction->getIdentifier(),
            ]);
            try {
                $response = $this->sendRequest($webhookInstruction, $message);
                $this->logger->debug('Webhook sent', [
                    'target_url' => $webhookInstruction->getTargetUrl(),
                    'response_code' => $response->getStatusCode(),
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Webhook sending failed', [
                    'failure_message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function sendRequest(WebhookInstruction $webhookInstruction, WebhookMessageInterface $message): ResponseInterface
    {
        $body = json_encode($message, JSON_THROW_ON_ERROR);
        $headers = $this->buildHeaders($webhookInstruction, $body);

        $options = [
            'headers' => $headers,
            'body' => $body,
        ];
        if (!$webhookInstruction->verifySSL()) {
            $options['verify'] = false;
        }

        return $this->requestFactory->request(
            $webhookInstruction->getTargetUrl(),
            $webhookInstruction->getHttpMethod(),
            $options
        );
    }

    private function buildHash(WebhookInstruction $webhookInstruction, string $body): string
    {
        return hash_hmac($this->algo, sprintf(
            '%s:%s',
            $webhookInstruction->getIdentifier(),
            $body
        ), $webhookInstruction->getSecret());
    }

    private function buildHeaders(WebhookInstruction $webhookInstruction, string $body): array
    {
        $headers = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['headers'] ?? [];
        $headers['Content-Type'] = 'application/json';
        $headers['Webhook-Signature-Algo'] = $this->algo;
        $headers = array_merge($headers, $webhookInstruction->getAdditionalHeaders());
        $headers['Webhook-Signature'] = $this->buildHash($webhookInstruction, $body);
        return $headers;
    }
}
