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

namespace TYPO3\CMS\Reactions\Tests\Functional;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Reaction\CreateRecordReaction;
use TYPO3\CMS\Reactions\ReactionRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ReactionRegistryTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    protected array $coreExtensionsToLoad = ['reactions'];

    protected ReactionRegistry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $reactions = $this->buildReactionMock();
        $this->subject = new ReactionRegistry($reactions);
    }

    /**
     * @test
     */
    public function getAvailableReactionTypes(): void
    {
        $types = iterator_to_array($this->subject->getAvailableReactionTypes()->getIterator());
        self::assertInstanceOf(CreateRecordReaction::class, reset($types));
        self::assertCount(1, $types);
    }

    /**
     * @test
     */
    public function getReactionByType(): void
    {
        self::assertInstanceOf(CreateRecordReaction::class, $this->subject->getReactionByType(CreateRecordReaction::getType()));
        self::assertNull($this->subject->getReactionByType('invalid'));
    }

    protected function buildReactionMock(): \IteratorAggregate
    {
        $class = new class () implements \IteratorAggregate {
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([CreateRecordReaction::getType() => GeneralUtility::makeInstance(CreateRecordReaction::class)]);
            }
        };

        return new $class();
    }
}
