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

namespace TYPO3\CMS\Webhooks\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Webhooks\Model\WebhookInstruction;
use TYPO3\CMS\Webhooks\Model\WebhookType;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * A factory to create webhook instructions from a database row.
 *
 * @internal not part of TYPO3's Core API
 */
class WebhookInstructionFactory
{
    private static array $defaults = [
        'method' => 'POST',
        'verify_ssl' => true,
        'additional_headers' => [],
        'name' => null,
        'description' => null,
        'webhook_type' => null,
        'identifier' => null,
        'uid' => null,
    ];

    public static function create(
        string $url,
        string $secret,
        string $method = 'POST',
        bool $verifySSL = true,
        array $additionalHeaders = [],
        string $name = null,
        string $description = null,
        WebhookType $webhookType = null,
        string $identifier = null,
        int $uid = null,
    ): WebhookInstruction {
        return new WebhookInstruction(
            $url,
            $secret,
            $method,
            $verifySSL,
            $additionalHeaders,
            $name,
            $description,
            $webhookType,
            $identifier,
            $uid
        );
    }

    public static function createFromRow(array $row): WebhookInstruction
    {
        $data = array_merge(self::$defaults, $row);

        if ($data['webhook_type'] !== null) {
            try {
                $data['webhook_type'] = GeneralUtility::makeInstance(WebhookTypesRegistry::class)
                    ->getWebhookByType($data['webhook_type']);
            } catch (\UnexpectedValueException $e) {
                // Webhook type not found
                $data['webhook_type'] = null;
            }
        }

        return new WebhookInstruction(
            $data['url'],
            $data['secret'],
            $data['method'],
            (bool)$data['verify_ssl'],
            $data['additional_headers'],
            $data['name'],
            $data['description'],
            $data['webhook_type'],
            $data['identifier'],
            $data['uid'],
        );
    }
}
