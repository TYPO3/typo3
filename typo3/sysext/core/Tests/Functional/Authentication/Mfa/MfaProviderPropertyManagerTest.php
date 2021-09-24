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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Mfa;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaProviderPropertyManagerTest extends FunctionalTestCase
{
    protected AbstractUserAuthentication $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/be_users.xml');

        $this->user = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $this->user->enablecolumns = ['deleted' => true];
        $this->user->setBeUserByUid(4);
    }

    /**
     * @test
     */
    public function createTest(): void
    {
        $propertyManager = $this->createPropertyManager('totp');

        self::assertEquals('totp', $propertyManager->getIdentifier());
        self::assertEquals($this->user, $propertyManager->getUser());
    }

    /**
     * @test
     */
    public function hasProviderEntryTest(): void
    {
        self::assertFalse($this->createPropertyManager('recovery-codes')->hasProviderEntry());
        self::assertTrue($this->createPropertyManager('totp')->hasProviderEntry());
    }

    /**
     * @test
     */
    public function hasPropertyTest(): void
    {
        $propertyManager = $this->createPropertyManager('totp');
        self::assertFalse($propertyManager->hasProperty('unknown'));
        self::assertTrue($propertyManager->hasProperty('active'));
    }

    /**
     * @test
     */
    public function getPropertyTest(): void
    {
        $propertyManager = $this->createPropertyManager('totp');
        self::assertNull($propertyManager->getProperty('unknown'));
        self::assertEquals('defaultValue', $propertyManager->getProperty('unknown', 'defaultValue'));
        self::assertTrue($propertyManager->getProperty('active'));
        self::assertEquals('KRMVATZTJFZUC53FONXW2ZJB', $propertyManager->getProperty('secret'));
    }

    /**
     * @test
     */
    public function getPropertiesTest(): void
    {
        $propertyManager = $this->createPropertyManager('recovery-codes');
        self::assertCount(0, $propertyManager->getProperties());
        $propertyManager = $this->createPropertyManager('totp');
        self::assertCount(3, $propertyManager->getProperties());
        self::assertEquals(
            [
                'active' => true,
                'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
                'attempts' => 2,
            ],
            $propertyManager->getProperties()
        );
    }

    /**
     * @test
     */
    public function updatePropertiesTest(): void
    {
        $propertyManager = $this->createPropertyManager('totp');

        // Ensure "updated" property is not set
        self::assertFalse($propertyManager->hasProperty('updated'));
        self::assertEquals(2, $propertyManager->getProperty('attempts'));

        $propertyManager->updateProperties([
            'lastUsed' => 1614012257,
            'attempts' => 3,
        ]);

        // "updated" property was automatically added
        self::assertTrue($propertyManager->hasProperty('updated'));
        self::assertEquals(1614012257, $propertyManager->getProperty('lastUsed'));
        self::assertEquals(3, $propertyManager->getProperty('attempts'));
        self::assertCount(5, $propertyManager->getProperties());

        // Ensure the data were also assigned to the user
        $userMfaData = json_decode($this->user->user['mfa'], true);
        self::assertEquals(1614012257, $userMfaData['totp']['lastUsed']);
        self::assertEquals(3, $userMfaData['totp']['attempts']);
        self::assertCount(5, $userMfaData['totp']);

        $propertyManager->updateProperties(['updated' => 123456789]);

        // "updated" property is properly set
        self::assertEquals(123456789, $propertyManager->getProperty('updated'));

        // Finally ensure, the data was actually written to the database
        $this->assertDatabaseValue(
            '{"totp":{"secret":"KRMVATZTJFZUC53FONXW2ZJB","active":true,"attempts":3,"lastUsed":1614012257,"updated":123456789}}'
        );
    }

    /**
     * @test
     */
    public function createProviderEntryThrowsExceptionOnAlreadyExistingEntryTest(): void
    {
        $this->expectExceptionCode(1612781782);
        $this->expectException(\InvalidArgumentException::class);
        $this->createPropertyManager('totp')->createProviderEntry(['key' => 'value']);
    }

    /**
     * @test
     */
    public function createProviderEntryTest(): void
    {
        $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $propertyManager = $this->createPropertyManager('recovery-codes');

        // Ensure entry does not yet exist
        self::assertFalse($propertyManager->hasProviderEntry());

        $propertyManager->createProviderEntry([
            'active' => true,
            'codes' => ['some-code', 'another-code'],
            'updated' => 123456789,
        ]);

        self::assertTrue($propertyManager->hasProviderEntry());
        self::assertCount(4, $propertyManager->getProperties());
        self::assertTrue($propertyManager->getProperty('active'));
        self::assertEquals(['some-code', 'another-code'], $propertyManager->getProperty('codes'));
        // Ensure "updated" is not overwritten
        self::assertEquals(123456789, $propertyManager->hasProperty('updated'));
        // Ensure "created" is automatically set
        self::assertTrue($propertyManager->hasProperty('created'));
        self::assertEquals($timestamp, $propertyManager->getProperty('created'));

        // Ensure the data were also assigned to the user
        $userMfaData = json_decode($this->user->user['mfa'], true);
        self::assertCount(4, $userMfaData['recovery-codes']);
        self::assertTrue($userMfaData['recovery-codes']['active']);
        self::assertEquals(['some-code', 'another-code'], $userMfaData['recovery-codes']['codes']);
        self::assertEquals(123456789, $userMfaData['recovery-codes']['updated']);
        self::assertTrue((bool)($userMfaData['recovery-codes']['created'] ?? false));
        self::assertEquals($timestamp, $userMfaData['recovery-codes']['created']);

        // Finally ensure, the data was actually written to the database
        $this->assertDatabaseValue(
            '{"totp":{"secret":"KRMVATZTJFZUC53FONXW2ZJB","active":true,"attempts":2},"recovery-codes":{"active":true,"codes":["some-code","another-code"],"updated":123456789,"created":' . $timestamp . '}}'
        );
    }

    /**
     * @test
     */
    public function deleteProviderEntryTest(): void
    {
        $propertyManager = $this->createPropertyManager('totp');
        self::assertTrue($propertyManager->hasProviderEntry());
        $propertyManager->deleteProviderEntry();
        self::assertFalse($propertyManager->hasProviderEntry());

        // Ensure the data were also assigned to the user
        $userMfaData = json_decode($this->user->user['mfa'], true);
        self::assertFalse((bool)($userMfaData['totp'] ?? false));

        // Finally ensure, the data was actually written to the database
        $this->assertDatabaseValue('[]');
    }

    protected function createPropertyManager(string $providerIdentifier): MfaProviderPropertyManager
    {
        return MfaProviderPropertyManager::create(
            $this->getContainer()->get(MfaProviderRegistry::class)->getProvider($providerIdentifier),
            $this->user
        );
    }

    protected function assertDatabaseValue(string $expected): void
    {
        self::assertEquals($expected, BackendUtility::getRecord(...['be_users', 4, 'mfa'])['mfa'] ?? null);
    }
}
