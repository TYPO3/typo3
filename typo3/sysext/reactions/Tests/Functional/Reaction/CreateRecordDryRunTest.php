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

namespace TYPO3\CMS\Reactions\Tests\Functional\Reaction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Reactions\Reaction\CreateRecordDryRun;
use TYPO3\CMS\Reactions\Reaction\CreateRecordDryRunResult;
use TYPO3\CMS\Reactions\Reaction\CreateRecordReaction;
use TYPO3\CMS\Reactions\Reaction\DryRunObstacle;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;
use TYPO3\CMS\Reactions\Tests\Functional\Fixtures\UserDependentItemsProcFunc;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CreateRecordDryRunTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CreateRecordDryRunTest.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function composesItemsAsTheImpersonatedUserAndRestoresTheOneAsking(): void
    {
        // Item processors read $GLOBALS['BE_USER'], which the endpoint sets to the user the
        // reaction impersonates. This one offers "42" to that user only.
        $GLOBALS['TCA']['pages']['columns']['layout']['config']['itemsProcFunc'] = UserDependentItemsProcFunc::class . '->addItemForGrantedEditor';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $askingUser = $GLOBALS['BE_USER'];
        $askingLanguageService = $GLOBALS['LANG'];

        $result = $this->dryRun(40, ['headline' => 'Hello', 'customer' => ['name' => 'Jane'], 'layout' => '42']);

        self::assertSame([], $result->obstacles);
        self::assertSame('granted_editor', $result->username);
        self::assertNull($result->resolution->error);
        // nav_title is granted through the group of the editor, so the groups were fetched.
        $fields = $result->resolution->fields;
        ksort($fields);
        self::assertSame(['layout' => '42', 'nav_title' => 'Jane', 'title' => 'Hello'], $fields);
        self::assertSame([], $result->resolution->skippedFields);
        self::assertSame($askingUser, $GLOBALS['BE_USER']);
        self::assertSame($askingLanguageService, $GLOBALS['LANG']);
        self::assertSame(0, $this->countPagesTitled('Hello'));
    }

    #[Test]
    public function labelsTheFieldsOfTheResolution(): void
    {
        $result = $this->dryRun(40, ['headline' => 'Hello']);

        self::assertEqualsCanonicalizing(['title', 'nav_title', 'layout'], array_keys($result->fieldLabels));
        self::assertStringContainsString(':title', $result->fieldLabels['title']);
    }

    #[Test]
    public function resolvesNothingWithoutAPayload(): void
    {
        $result = $this->dryRun(41, null);

        self::assertNull($result->resolution);
        self::assertSame([], $result->fieldLabels);
        self::assertSame([DryRunObstacle::DISABLED], $result->obstacles);
    }

    public static function namesWhyTheEndpointWouldCreateNoRecordDataProvider(): array
    {
        return [
            'disabled reaction' => [41, [DryRunObstacle::DISABLED]],
            'reaction not started yet' => [42, [DryRunObstacle::SCHEDULED]],
            'expired reaction' => [43, [DryRunObstacle::EXPIRED]],
            'no impersonated user' => [44, [DryRunObstacle::NO_USER]],
            'table not modifiable by the user' => [45, [DryRunObstacle::TABLE_NOT_PERMITTED]],
            'storage page not accessible to the user' => [46, [DryRunObstacle::PAGE_NOT_PERMITTED]],
            'storage page missing' => [47, [DryRunObstacle::PAGE_MISSING]],
        ];
    }

    #[DataProvider('namesWhyTheEndpointWouldCreateNoRecordDataProvider')]
    #[Test]
    public function namesWhyTheEndpointWouldCreateNoRecord(int $uid, array $expected): void
    {
        self::assertSame($expected, $this->dryRun($uid, ['headline' => 'Hello'])->obstacles);
    }

    private function dryRun(int $uid, ?array $payload): CreateRecordDryRunResult
    {
        $reaction = $this->get(ReactionRepository::class)->getReactionRecordByUid($uid);

        return $this->get(CreateRecordDryRun::class)->run($reaction, $this->get(CreateRecordReaction::class), $payload);
    }

    private function countPagesTitled(string $title): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder->count('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title)))
            ->executeQuery()
            ->fetchOne();
    }
}
