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

namespace TYPO3\CMS\Backend\Tests\Unit\ElementBrowser;

use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ElementBrowserRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registrationRequiresInterface(): void
    {
        $elementBrowser = [
            new class() {
            },
            $this->getElementBrowser('valid-identifier'),
        ];

        $elementBrowserRegistry = new ElementBrowserRegistry($elementBrowser);

        self::assertNotNull($elementBrowserRegistry->getElementBrowser('valid-identifier'));
        self::assertCount(1, $elementBrowserRegistry->getElementBrowsers());
        self::assertTrue($elementBrowserRegistry->hasElementBrowser('valid-identifier'));
    }

    /**
     * @test
     */
    public function registrationThrowsExceptionOnEmptyIdentifier(): void
    {
        $elementBrowser = [
            $this->getElementBrowser(),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1647241084);

        new ElementBrowserRegistry($elementBrowser);
    }

    /**
     * @test
     */
    public function registrationThrowsExceptionOnDuplicateIdentifier(): void
    {
        $elementBrowser = [
            $this->getElementBrowser('duplicate'),
            $this->getElementBrowser('duplicate'),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1647241085);

        new ElementBrowserRegistry($elementBrowser);
    }

    /**
     * @test
     */
    public function registrationThrowsExceptionOnRequestingInvalidIdentifier(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1647241086);

        (new ElementBrowserRegistry([]))->getElementBrowser('non-existent-identifer');
    }

    protected function getElementBrowser(string $identifier = ''): ElementBrowserInterface
    {
        return new class($identifier) implements ElementBrowserInterface {
            private string $identifier;
            public function __construct(string $identifier)
            {
                $this->identifier = $identifier;
            }
            public function getIdentifier(): string
            {
                return $this->identifier;
            }
            public function render()
            {
                return '';
            }
            public function processSessionData($data)
            {
                return [];
            }
        };
    }
}
