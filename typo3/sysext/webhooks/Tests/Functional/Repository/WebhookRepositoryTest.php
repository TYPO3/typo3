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

namespace TYPO3\CMS\Webhooks\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Webhooks\Model\WebhookInstruction;
use TYPO3\CMS\Webhooks\Repository\WebhookRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WebhookRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['webhooks'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_webhooks.csv');
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_webhook')
            ->update('sys_webhook', ['disabled' => 1], ['uid' => 2]);
    }

    private static function getUids(array $webhooks): array
    {
        return array_values(array_map(static fn(WebhookInstruction $webhook): int => $webhook->getUid(), $webhooks));
    }

    #[Test]
    public function findAllReturnsDisabledWebhooksAfterARestrictedLookup(): void
    {
        $subject = $this->get(WebhookRepository::class);
        self::assertEqualsCanonicalizing([1, 3], self::getUids($subject->setApplyDefaultRestrictions(true)->findAll()));
        self::assertEqualsCanonicalizing([1, 2, 3], self::getUids($subject->setApplyDefaultRestrictions(false)->findAll()));
    }

    #[Test]
    public function findAllOmitsDisabledWebhooksAfterAnUnrestrictedLookup(): void
    {
        $subject = $this->get(WebhookRepository::class);
        self::assertEqualsCanonicalizing([1, 2, 3], self::getUids($subject->findAll()));
        self::assertEqualsCanonicalizing([1, 3], self::getUids($subject->setApplyDefaultRestrictions(true)->findAll()));
    }
}
