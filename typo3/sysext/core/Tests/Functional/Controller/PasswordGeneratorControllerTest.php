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

namespace TYPO3\CMS\Core\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Controller\PasswordGeneratorController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGenerator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PasswordGeneratorControllerTest extends FunctionalTestCase
{
    private PasswordGeneratorController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->get(PasswordGeneratorController::class);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_admin.csv');

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function generateWithInvalidInputRespondsWithErrorDataProvider(): iterable
    {
        yield 'invalid password policy' => [
            ['passwordPolicy' => 'invalid'],
            null,
            null,
        ];
        yield 'invalid class name' => [
            ['passwordPolicy' => 'default'],
            \stdClass::class,
            [],
        ];
        yield 'empty class name' => [
            ['passwordPolicy' => 'default'],
            '',
            [],
        ];
        yield 'class name as array' => [
            ['passwordPolicy' => 'default'],
            [],
            [],
        ];
        yield 'options as string' => [
            ['passwordPolicy' => 'default'],
            \stdClass::class,
            'foo',
        ];
        yield 'Class stdClass does not implement PasswordGeneratorInterface' => [
            ['passwordPolicy' => 'default'],
            \stdClass::class,
            [],
        ];
    }

    #[Test]
    #[DataProvider('generateWithInvalidInputRespondsWithErrorDataProvider')]
    public function generateWithInvalidInputRespondsWithError(array $parsedBody, mixed $classNameOverride, mixed $options): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator']['className'] = $classNameOverride;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator']['options'] = $options;

        $request = (new ServerRequest())->withParsedBody($parsedBody);
        $response = $this->controller->generate($request);

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($result['success']);
    }

    public static function generateReturnsSuccessfulJsonResponseDataProvider(): iterable
    {
        yield 'generate via policy' => [
            ['passwordPolicy' => 'default'],
            function () {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator'] = [
                    'className' => PasswordGenerator::class,
                    'options' => [],
                ];
            },
        ];

        // @deprecated. Remove in TYPO3 15.
        yield 'generate via raw rules' => [
            [
                'passwordRules' => [
                    'length' => 12,
                ],
            ],
            null,
        ];
    }

    #[Test]
    #[DataProvider('generateReturnsSuccessfulJsonResponseDataProvider')]
    #[IgnoreDeprecations]
    public function generateReturnsSuccessfulJsonResponse(array $parsedBody, ?callable $setupConfig = null): void
    {
        if ($setupConfig !== null) {
            $setupConfig();
        }

        $request = (new ServerRequest())->withParsedBody($parsedBody);
        $response = $this->controller->generate($request);

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($result['success'] ?? null);
        self::assertNotEmpty($result['password'] ?? null);
    }

    public static function generateRespondsWithErrorOnMisconfigurationDataProvider(): iterable
    {
        yield 'missing className' => [
            ['passwordPolicy' => 'default'],
            ['options' => []],
        ];
        yield 'invalid className' => [
            ['passwordPolicy' => 'default'],
            ['className' => \stdClass::class, 'options' => []],
        ];
        yield 'missing options array' => [
            ['passwordPolicy' => 'default'],
            ['className' => PasswordGenerator::class],
        ];
    }

    #[Test]
    #[DataProvider('generateRespondsWithErrorOnMisconfigurationDataProvider')]
    public function generateRespondsWithErrorOnMisconfiguration(array $parsedBody, array $configOverride): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator'] = $configOverride;

        $request = (new ServerRequest())->withParsedBody($parsedBody);
        $response = $this->controller->generate($request);
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($result['success']);
    }

    #[Test]
    #[IgnoreDeprecations]
    /**
     * @deprecated Remove in TYPO3 15.
     */
    public function generateReturnsFalseOnInvalidRules(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'passwordRules' => ['length' => 4],
        ]);

        $response = $this->controller->generate($request);
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($result['success']);
    }
}
