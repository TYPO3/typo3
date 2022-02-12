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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Mfa\Provider;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\RecoveryCodes;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RecoveryCodesProviderTest extends FunctionalTestCase
{
    private BackendUserAuthentication $user;
    private MfaProviderManifestInterface $subject;

    protected $configurationToUseInTestInstance = [
        'BE' => [
            'passwordHashing' => [
                'className' => Argon2iPasswordHash::class,
                'options' => [
                    // Reduce default costs for quicker unit tests
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 2,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->user = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->createFromUserPreferences($this->user);
        $this->subject = $this->getContainer()->get(MfaProviderRegistry::class)->getProvider('recovery-codes');
    }

    /**
     * @test
     */
    public function canProcessTest(): void
    {
        self::assertFalse($this->subject->canProcess(new ServerRequest('https://example.com', 'POST')));

        // Add necessary query parameter
        self::assertTrue($this->subject->canProcess(
            (new ServerRequest('https://example.com', 'POST'))
                ->withQueryParams(['rc' => '12345678'])
        ));
    }

    /**
     * @test
     */
    public function isActiveTest(): void
    {
        self::assertFalse($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Activate provider
        $this->setupUser(['recovery-codes' => ['active' => true]]);
        self::assertTrue($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    /**
     * @test
     */
    public function isLockedTest(): void
    {
        self::assertFalse($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Lock provider by setting attempts=3
        $this->user->user['mfa'] = json_encode(['recovery-codes' => ['active' => true, 'attempts' => 3]]);
        self::assertTrue($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Lock provider by removing the codes
        $this->user->user['mfa'] = json_encode(['recovery-codes' => ['active' => true, 'codes' => []]]);
        self::assertTrue($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    /**
     * @test
     */
    public function verifyTest(): void
    {
        $code = '12345678';
        $hash = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword($code);

        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => [$hash], 'attempts' => 0]]);

        $request = (new ServerRequest('https://example.com', 'POST'));
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);

        self::assertFalse(
            $this->subject->verify(
                $request->withQueryParams(['rc' => '87654321']),
                $propertyManager
            )
        );

        self::assertTrue(
            $this->subject->verify(
                $request->withQueryParams(['rc' => $code]),
                $propertyManager
            )
        );
    }

    /**
     * @test
     */
    public function activateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);

        self::assertFalse($this->subject->activate($request->withParsedBody(['totp' => '123456']), $propertyManager));

        // Setup form data to activate provider
        $this->setupUser(['recovery-codes' => ['active' => false]]);
        $codes = GeneralUtility::makeInstance(RecoveryCodes::class, 'BE')->generatePlainRecoveryCodes();
        $parsedBody = [
            'recoveryCodes' => implode(PHP_EOL, $codes),
            'checksum' => GeneralUtility::hmac(json_encode($codes) ?: '', 'recovery-codes-setup'),
        ];
        self::assertTrue($this->subject->activate($request->withParsedBody($parsedBody), $propertyManager));
    }

    /**
     * @test
     */
    public function deactivateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        self::assertFalse($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        $this->setupUser(['recovery-codes' => ['active' => false]]);
        self::assertFalse($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Only an active provider can be deactivated
        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => ['some-code'], 'attempts' => 0]]);
        self::assertTrue($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    /**
     * @test
     */
    public function unlockTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        self::assertFalse($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => ['some-code'], 'attempts' => 0]]);
        self::assertFalse($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Only an active and locked provider can be unlocked
        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => [], 'attempts' => 3]]);
        self::assertTrue($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
        $message = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0];
        self::assertEquals('Your recovery codes were automatically updated!', $message->getTitle());
    }

    /**
     * @test
     */
    public function updateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        self::assertFalse($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        $this->setupUser(['recovery-codes' => ['active' => true, 'attempts' => 3]]);
        self::assertFalse($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Only an active and unlocked provider can be updated
        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => ['some-code'], 'attempts' => 0]]);
        $request = $request->withParsedBody(['name' => 'some name', 'regenerateCodes' => true]);
        self::assertTrue($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
        $message = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0];
        self::assertEquals('Recovery codes successfully regenerated', $message->getTitle());
    }

    /**
     * @test
     */
    public function setupFailsIfNoOtherMfaProviderIsActive(): void
    {
        $this->expectException(PropagateResponseException::class);
        $this->subject->handleRequest(
            new ServerRequest('https://example.com', 'GET'),
            MfaProviderPropertyManager::create($this->subject, $this->user),
            MfaViewType::SETUP
        );
    }

    /**
     * @test
     */
    public function setupReturnsHtmlWithRecoveryCodes(): void
    {
        $this->setupUser();
        $response = $this->subject->handleRequest(
            new ServerRequest('https://example.com', 'GET'),
            MfaProviderPropertyManager::create($this->subject, $this->user),
            MfaViewType::SETUP
        );
        self::assertStringContainsString('<textarea type="text" id="recoveryCodes"', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function editViewTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $this->setupUser([
            'recovery-codes' => [
                'codes' => ['some-code', 'another-code'],
                'name' => 'some name',
                'updated' => 1616099471,
                'lastUsed' => 1616099472,
            ],
        ]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::EDIT)->getBody()->getContents();

        self::assertMatchesRegularExpression('/<td>.*Name.*<td>.*some name/s', $response);
        self::assertMatchesRegularExpression('/<td>.*Recovery codes left.*<td>.*2/s', $response);
        self::assertMatchesRegularExpression('/<td>.*Last updated.*<td>.*18-03-21/s', $response);
        self::assertMatchesRegularExpression('/<td>.*Last used.*<td>.*18-03-21/s', $response);
        self::assertMatchesRegularExpression('/<input.*id="regenerateCodes"/s', $response);
    }

    /**
     * @test
     */
    public function authViewTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => ['some-code']]]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::AUTH)->getBody()->getContents();

        self::assertMatchesRegularExpression('/<input.*id="recoveryCode"/s', $response);

        // Lock the provider by setting attempts=3
        $this->setupUser(['recovery-codes' => ['active' => true, 'codes' => ['some-code'], 'attempts' => 3]]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::AUTH)->getBody()->getContents();

        self::assertStringContainsString('The maximum attempts for this provider are exceeded.', $response);
    }

    protected function setupUser(array $additional = []): void
    {
        $this->user->user['mfa'] = json_encode(
            array_replace_recursive(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']], $additional)
        );
    }
}
