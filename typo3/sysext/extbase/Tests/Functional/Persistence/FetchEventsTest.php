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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class FetchEventsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FetchEventsTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * Loading a post fires `ModifyQueryBeforeFetchingObjectDataEvent` and `ModifyResultAfterFetchingObjectDataEvent` as
     * a pair around each object data query. This pins the pairing rule, not the number or the order of the relation
     * queries: the first pair is the post query, events strictly alternate before and after, every "after" belongs to
     * the query of the "before" right before it, and at least one more pair follows for the relations.
     */
    #[Test]
    public function fetchEventsFireBeforeAndAfterObjectDataInOrder(): void
    {
        // Given: listeners that record kind, type, identity of the query and the number of rows
        $events = [];
        $container = $this->get('service_container');
        $container->set('before-fetch-listener', static function (ModifyQueryBeforeFetchingObjectDataEvent $event) use (&$events): void {
            $events[] = ['before', substr((string)strrchr($event->getQuery()->getType(), '\\'), 1), spl_object_id($event->getQuery()), 0];
        });
        $container->set('after-fetch-listener', static function (ModifyResultAfterFetchingObjectDataEvent $event) use (&$events): void {
            $events[] = ['after', substr((string)strrchr($event->getQuery()->getType(), '\\'), 1), spl_object_id($event->getQuery()), count($event->getResult())];
        });
        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyQueryBeforeFetchingObjectDataEvent::class, 'before-fetch-listener');
        $listenerProvider->addListener(ModifyResultAfterFetchingObjectDataEvent::class, 'after-fetch-listener');

        // When: post 1 is loaded
        $this->get(PostRepository::class)->findByUid(1);

        // Then: the post pair comes first, all pairs alternate before/after on the same query,
        // and further pairs follow for the relations
        $pairs = [];
        $pairChecks = [];
        $kinds = [];
        foreach ($events as $index => $event) {
            $kinds[] = $event[0];
            if ($event[0] !== 'before') {
                continue;
            }
            $after = $events[$index + 1] ?? null;
            $pairs[] = sprintf('before %s / after %s: %s rows', $event[1], $after[1] ?? 'none', $after[3] ?? 'none');
            $pairChecks[] = $after !== null && $after[0] === 'after' && $after[2] === $event[2]
                ? 'pair ok'
                : sprintf('pair %d: before %s is not followed by an after of the same query', count($pairs), $event[1]);
        }
        self::assertSame('before Post / after Post: 1 rows', $pairs[0] ?? 'no events');
        self::assertSame(
            array_map(static fn(int $i): string => $i % 2 === 0 ? 'before' : 'after', array_keys($kinds)),
            $kinds
        );
        self::assertSame(array_fill(0, count($pairChecks), 'pair ok'), $pairChecks);
        self::assertGreaterThan(1, count($pairs), 'relation queries fire further pairs: ' . implode(', ', $pairs));
    }
}
