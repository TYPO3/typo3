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

namespace TYPO3\CMS\Beuser\Tests\Functional\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Beuser\Event\AfterBackendUserListConstraintsAssembledFromDemandEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUserRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'beuser',
    ];

    #[Test]
    public function findDemandedDispatchesModifyBackendUserListConstraintsEvent(): void
    {
        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-backend-user-list-constraints-assembled-from-demand-event-is-dispatched',
            static function (AfterBackendUserListConstraintsAssembledFromDemandEvent $event) use (&$dispatchedEvents) {
                $event->constraints[] = $event->query->equals('admin', 0);
                $dispatchedEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            AfterBackendUserListConstraintsAssembledFromDemandEvent::class,
            'after-backend-user-list-constraints-assembled-from-demand-event-is-dispatched'
        );
        $subject = $this->get(BackendUserRepository::class);
        $subject->findDemanded(new Demand());
        self::assertCount(1, $dispatchedEvents);
        self::assertCount(1, $dispatchedEvents[0]->constraints);
        /** @var Comparison $constraint */
        $constraint = $dispatchedEvents[0]->constraints[0];
        self::assertEquals('admin', $constraint->getOperand1()->getPropertyName());
        self::assertEquals(0, $constraint->getOperand2());
        self::assertEquals(QueryInterface::OPERATOR_EQUAL_TO, $constraint->getOperator());
    }
}
