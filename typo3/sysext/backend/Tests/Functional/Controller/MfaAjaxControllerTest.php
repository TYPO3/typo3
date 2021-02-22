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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Controller\MfaAjaxController;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaAjaxControllerTest extends FunctionalTestCase
{
    protected MfaAjaxController $subject;
    protected ServerRequest $request;

    protected $backendUserFixture = 'EXT:core/Tests/Functional/Authentication/Fixtures/be_users.xml';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new MfaAjaxController($this->getContainer()->get(MfaProviderRegistry::class));

        $this->request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    /**
     * @test
     * @dataProvider handleRequestHandlesInvalidRequestTestDataProvider
     */
    public function handleRequestHandlesInvalidRequestTest(array $parsedBody): void
    {
        $response = $this->parseResponse($this->subject->handleRequest($this->request->withParsedBody($parsedBody)));

        self::assertFalse($response['success']);
        self::assertEquals('Invalid request could not be processed', $response['message']);
    }

    public function handleRequestHandlesInvalidRequestTestDataProvider(): \Generator
    {
        yield 'No parameters' => [[]];
        yield 'Invalid action' => [['action' => 'unknown']];
        yield 'Missing user' => [['action' => 'deactivate']];
        yield 'Missing table' => [['action' => 'deactivate', 'userId' => 5]];
        yield 'Invalid table' => [['action' => 'deactivate', 'userId' => 5, 'tableName' => 'some_table']];
    }

    /**
     * @test
     */
    public function handleRequestReturnsInvalidRequestOnInsufficientPermissionsTest(): void
    {
        // Make the target user a system maintainer. Since the current user (1)
        // is only admin, he is not allowed to deactivate the providers, nor MFA.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] = ['5'];

        $response = $this->parseResponse(
            $this->subject->handleRequest(
                $this->request->withParsedBody([
                    'action' => 'deactivate',
                    'userId' => 5,
                    'tableName' => 'be_users'
                ])
            )
        );

        self::assertFalse($response['success']);
        self::assertEquals('Your are not allowed to perfom this action', $response['message']);
    }

    /**
     * @test
     * @dataProvider handleRequestHandlesDeactivationRequestTestDataProvider
     */
    public function handleRequestHandlesDeactivationRequestTest(
        array $parsedBody,
        bool $success,
        string $message,
        int $remaining
    ): void {
        $response = $this->parseResponse(
            $this->subject->handleRequest(
                $this->request->withParsedBody(
                    array_replace_recursive([
                        'action' => 'deactivate',
                        'tableName' => 'be_users'
                    ], $parsedBody)
                )
            )
        );

        self::assertEquals($success, $response['success']);
        self::assertEquals($message, $response['message']);
        self::assertEquals($remaining, $response['remaining']);
    }

    public function handleRequestHandlesDeactivationRequestTestDataProvider(): \Generator
    {
        yield 'No deactivation because no active providers' => [
            ['userId' => 3],
            false,
            'No provider has been deactivated',
            0
        ];
        yield 'Requested provider can not be found' => [
            ['userId' => 3, 'provider' => 'unknown'],
            false,
            'Provider unknown could not be found',
            0
        ];
        yield 'Does not deactivate an inactive provider' => [
            ['userId' => 3, 'provider' => 'recovery-codes'],
            false,
            'Could not deactivate provider Recovery codes',
            0
        ];
        yield 'Deactivates all providers on missing provider parameter' => [
            ['userId' => 5],
            true,
            'Successfully deactivated all active providers for user mfa_admin_locked',
            0
        ];
        yield 'Deactivates requested provider' => [
            ['userId' => 5, 'provider' => 'recovery-codes'],
            true,
            'Successfully deactivated provider Recovery codes for user mfa_admin_locked',
            1
        ];
        yield 'Deactivation of last main provider does also deactivate recovery codes' => [
            ['userId' => 5, 'provider' => 'totp'],
            true,
            'Successfully deactivated provider Time-based one-time password for user mfa_admin_locked',
            0
        ];
    }

    protected function parseResponse(ResponseInterface $response): array
    {
        $response = json_decode($response->getBody()->getContents(), true);

        return [
            'success' => (bool)($response['success'] ?? false),
            'message' => (string)(array_shift($response['status'])['message'] ?? ''),
            'remaining' => (int)($response['remaining'] ?? 0),
        ];
    }
}
