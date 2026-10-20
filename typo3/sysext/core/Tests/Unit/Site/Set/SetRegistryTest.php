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

namespace TYPO3\CMS\Core\Tests\Unit\Site\Set;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Set\SetCollector;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetRegistryTest extends UnitTestCase
{
    public static function setRegistrationOrderDataProvider(): array
    {
        return [
            'optional target registered last' => [false],
            'optional target registered first' => [true],
        ];
    }

    #[DataProvider('setRegistrationOrderDataProvider')]
    #[Test]
    public function optionalSetsWithMissingDependenciesDoNotCauseCircularDependencies(bool $reverseOrder): void
    {
        $sets = [
            new SetDefinition('a', 'A', optionalDependencies: ['b']),
            new SetDefinition('b', 'B', dependencies: ['a', 'missing']),
        ];
        $setCollector = new SetCollector();
        foreach ($reverseOrder ? array_reverse($sets) : $sets as $set) {
            $setCollector->add($set);
        }
        $subject = new SetRegistry(
            new DependencyOrderingService(),
            'sets',
            self::createStub(PhpFrontend::class),
            $setCollector,
            new NullLogger(),
        );

        self::assertSame(['a'], array_keys($subject->getAllSets()));
        self::assertSame([$sets[0]], $subject->getSets('a'));
        self::assertSame([
            'b' => [
                'error' => SetError::missingDependency,
                'name' => 'b',
                'context' => 'missing',
            ],
        ], $subject->getInvalidSets());
    }

    #[Test]
    public function sharedOptionalDependenciesAreCheckedWithLinearComplexity(): void
    {
        $setCollector = new SetCollector();
        $numberOfSets = 20;
        for ($i = 0; $i < $numberOfSets; $i++) {
            $optionalDependencies = [];
            for ($j = $i + 1; $j <= min($i + 2, $numberOfSets - 1); $j++) {
                $optionalDependencies[] = 'set-' . $j;
            }
            $setCollector->add(new SetDefinition('set-' . $i, 'Set ' . $i, optionalDependencies: $optionalDependencies));
        }
        $subject = new class (
            new DependencyOrderingService(),
            'sets',
            self::createStub(PhpFrontend::class),
            $setCollector,
            new NullLogger(),
        ) extends SetRegistry {
            public int $circularDependencyChecks = 0;

            protected function checkCircularDependencies(array $sets, SetDefinition $set, array $path = [], array &$checked = []): ?string
            {
                $this->circularDependencyChecks++;
                return parent::checkCircularDependencies($sets, $set, $path, $checked);
            }
        };

        self::assertCount($numberOfSets, $subject->getAllSets());
        self::assertSame([], $subject->getInvalidSets());
        self::assertLessThanOrEqual(3 * $numberOfSets, $subject->circularDependencyChecks);
    }
}
