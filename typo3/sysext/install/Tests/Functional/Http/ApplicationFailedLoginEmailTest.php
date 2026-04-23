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

namespace TYPO3\CMS\Install\Tests\Functional\Http;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Install\Http\Application;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ApplicationFailedLoginEmailTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['install'];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            // Non-empty to satisfy Maintenance middleware integrity check, value irrelevant.
            'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=1$placeholder',
            'warning_email_addr' => 'warning@example.test',
        ],
        'EXTENSIONS' => [
            'backend' => [
                'loginLogo' => 'URI:https://example.com/logo.png',
            ],
        ],
        'MAIL' => [
            // Don't actually send anything.
            'transport' => 'null',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        EnableFileService::createInstallToolEnableFile();
    }

    protected function tearDown(): void
    {
        EnableFileService::removeInstallToolEnableFile();
        parent::tearDown();
    }

    #[Test]
    public function failedLoginRendersNotificationMail(): void
    {
        $request = (new ServerRequest(
            new Uri('http://localhost/?install%5Bcontroller%5D=maintenance&install%5Bcontext%5D=install'),
            'POST',
            'php://input',
            [],
            [
                'SCRIPT_NAME' => '/index.php',
                'HTTP_HOST' => 'localhost',
                'SERVER_NAME' => 'localhost',
                'HTTPS' => 'off',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
        ))
            ->withQueryParams([
                'install' => [
                    'controller' => 'maintenance',
                    'context' => 'install',
                ],
            ])
            ->withParsedBody([
                'install' => [
                    'controller' => 'maintenance',
                    'context' => 'install',
                    'action' => 'login',
                    'password' => 'wrong-password',
                ],
            ]);

        try {
            $container = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            $response = $container->get(Application::class)->handle($request);
            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString('"success":false', (string)$response->getBody());
        } finally {
            // Middleware\Maintenance registers a FileSessionHandler and calls session_start()
            // for the login action. Close the session while SYS.encryptionKey is still in
            // $GLOBALS['TYPO3_CONF_VARS'] — otherwise PHP's shutdown-time session_write_close()
            // fatals after the global has been reset by PHPUnit or teardown.
            // Kinda hacky, but currently no better idea.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_abort();
            }
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }
    }
}
