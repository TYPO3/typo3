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

namespace TYPO3\CMS\Styleguide;

use TYPO3\CMS\Core\Cache\CacheEntry;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @internal
 */
final class DummyDumpContentProvider
{
    public static function getTestData(): array
    {
        $simpleObject = new \stdClass();
        $simpleObject->property1 = 'property1 value';
        $simpleObject->property2 = 'property2 value';
        $simpleObject->longtext = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';
        $simpleObject->{'<script>alert(1)</script>'} = 'xss in property name';

        $anonymousObject = new class () {
            public function __construct(
                public readonly ?string $foo = 'bar',
                protected readonly string $bar = 'baz',
                private readonly string|bool $baz = false,
            ) {}

            public function doBaz(): void
            {
                var_dump($this->baz);
            }
        };

        $closure = static function (
            string $parameter1,
            ?string $p2,
            bool $foo,
            int $bar,
            string|bool|int|array $any,
            float &$byReference,
            ?object $baz = null,
        ): void {
            var_dump(func_get_args());
        };

        $lambda = static fn(
            string $parameter1,
            ?string $p2,
            bool $foo,
            int $bar,
            string|bool|int|array $any,
            float &$byReference,
            bool ...$variadic,
        ): array => func_get_args();

        /* //does not work currently
        $iterable = static function(): \Generator {
            yield 1;
            yield 2;
            yield 3;
        };
        //*/

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($simpleObject);

        return [
            'nested' => [
                'data' => [
                    'exampleObject' => $simpleObject,
                ],
            ],
            'simple object' => new CacheEntry('name', 'content', $closure),
            'Anonymous Object' => $anonymousObject,
            // does not work currently
            //'simple iterable' => $iterable(),
            'closure' => $closure,
            'lambda' => $lambda,
            'example' => $simpleObject,
            'stringContent' => 'Lorem ipsum dolor sit amet',
            'integer' => 1337,
            'float' => 13.37,
            'enabled' => true,
            'disabled' => false,
            'datetime' => \DateTime::createFromFormat(\DateTimeInterface::ATOM, '2005-08-15T15:52:01+00:00'),
            'datetime immutable' => \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, '2005-08-15T15:52:01+00:00'),
            'empty array' => [],
            'array object' => new \ArrayObject(['foo' => 'bar']),
            'empty array object' => new \ArrayObject(),
            '<script>alert(1)</script>' => 'xss in array key',
            'escape sequences' => '[31;42mThis must not be red in terminal.[0m',
            'htmlString' => '<html><script>alert(1)</script></html>',
            'extbase' => [
                'entity' => new Category(),
                'value object' => new class () extends AbstractValueObject {
                    public string $name = '';
                    public ?object $object = null;
                    public ?int $uid = null;
                },
                'object storage' => $objectStorage,
                'repository' => new Repository(),
            ],
        ];
    }
}
