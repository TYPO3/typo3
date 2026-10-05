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

namespace TYPO3\CMS\Core\Tests\Functional\Site;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\TcaSiteSetCollector;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaSiteSetCollectorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function invalidSetIsListedBelowDividerWhenNotSelected(): void
    {
        $items = $this->populate('typo3tests/set-1');

        $values = array_column($items, 'value');
        $dividerPosition = array_search('--div--', $values, true);
        self::assertIsInt($dividerPosition);

        $invalidSetPosition = array_search('typo3tests/invalid-settings-definitions-missing-properties', $values, true);
        self::assertIsInt($invalidSetPosition);
        self::assertGreaterThan($dividerPosition, $invalidSetPosition);
        self::assertStringStartsWith('Invalid value (', $items[$invalidSetPosition]['label']);

        self::assertLessThan($dividerPosition, array_search('typo3tests/set-1', $values, true));
    }

    #[Test]
    public function invalidSetIsListedOnceAndReportedWhenSelected(): void
    {
        $items = $this->populate('typo3tests/invalid-settings-definitions-missing-properties');

        $values = array_column($items, 'value');
        self::assertCount(1, array_keys($values, 'typo3tests/invalid-settings-definitions-missing-properties', true));
        self::assertCount(1, $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function invalidSetIsNotReportedWhenNotSelected(): void
    {
        $this->populate('typo3tests/set-1');

        self::assertCount(0, $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    private function populate(string $selectedSets): array
    {
        $fieldConfiguration = [
            'items' => [],
            'field' => 'dependencies',
            'row' => [
                'identifier' => 'test',
                'dependencies' => $selectedSets,
            ],
        ];
        $this->get(TcaSiteSetCollector::class)->populateSiteSets($fieldConfiguration);
        return $fieldConfiguration['items'];
    }
}
