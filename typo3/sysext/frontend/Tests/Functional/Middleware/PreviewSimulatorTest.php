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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewSimulatorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/previewSimulatorCases.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    public static function userGroupSimulationDataProvider(): array
    {
        return [
            'backend user without frontend user' => ['1', 0, PHP_INT_MAX, true],
            'frontend user without the simulated group' => ['1', 2, PHP_INT_MAX, true],
            'frontend user with the simulated group' => ['1', 1, 1, false],
            'frontend user with the simulated group as subgroup' => ['1', 3, 3, false],
            'frontend user with only one of the simulated groups' => ['1,2', 1, PHP_INT_MAX, true],
            'frontend user with all simulated groups' => ['1,2', 4, 4, false],
        ];
    }

    #[DataProvider('userGroupSimulationDataProvider')]
    #[Test]
    public function userGroupIsSimulatedOnlyIfFrontendUserIsNotMemberOfAllGroups(string $simulatedUserGroups, int $frontendUserId, int $expectedUserId, bool $expectedPreview): void
    {
        $context = (new InternalRequestContext())->withBackendUserId(1)->withWorkspaceId(0);
        if ($frontendUserId > 0) {
            $context = $context->withFrontendUserId($frontendUserId);
        }

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.com/protected'))->withQueryParameter('ADMCMD_simUser', $simulatedUserGroups),
            $context
        );
        $body = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('user=' . $expectedUserId . ';preview=' . ($expectedPreview ? '1' : '') . ';', $body);
        self::assertSame($expectedPreview, str_contains($body, 'id="typo3-preview-info"'));
    }
}
