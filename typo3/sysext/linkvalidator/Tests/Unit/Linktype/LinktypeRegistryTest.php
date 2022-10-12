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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Linktype;

use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LinktypeRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registrationRequiresInterface(): void
    {
        $linktypes = [
            new class () {
            },
            $this->getLinkType('valid-identifier'),
        ];

        $linkTypeRegistry = new LinktypeRegistry($linktypes);

        self::assertNotNull($linkTypeRegistry->getLinktype('valid-identifier'));
        self::assertCount(1, $linkTypeRegistry->getLinktypes());
        self::assertEquals(['valid-identifier'], $linkTypeRegistry->getIdentifiers());
    }

    /**
     * @test
     */
    public function registrationThrowsExceptionOnEmptyIdentifier(): void
    {
        $linktypes = [
            $this->getLinkType(),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1644932383);

        new LinktypeRegistry($linktypes);
    }

    /**
     * @test
     */
    public function registrationThrowsExceptionOnDuplicateIdentifier(): void
    {
        $linktypes = [
            $this->getLinkType('duplicate'),
            $this->getLinkType('duplicate'),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1644932384);

        new LinktypeRegistry($linktypes);
    }

    protected function getLinkType(string $identifier = ''): LinktypeInterface
    {
        return new class ($identifier) implements LinktypeInterface {
            private string $identifier;
            public function __construct(string $identifier)
            {
                $this->identifier = $identifier;
            }
            public function getIdentifier(): string
            {
                return $this->identifier;
            }
            public function checkLink($url, $softRefEntry, $reference)
            {
                return true;
            }
            public function setAdditionalConfig(array $config): void
            {
            }
            public function fetchType($value, $type, $key)
            {
                return '';
            }
            public function getErrorParams()
            {
                return [];
            }
            public function getBrokenUrl($row)
            {
                return '';
            }
            public function getErrorMessage($errorParams)
            {
                return '';
            }
        };
    }
}
