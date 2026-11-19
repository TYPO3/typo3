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

namespace TYPO3\CMS\Seo\Tests\Unit\MetaTag;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Seo\MetaTag\CanonicalMetaTagManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CanonicalMetaTagManagerTest extends UnitTestCase
{
    #[Test]
    public function handlesOnlyCanonicalProperty(): void
    {
        $manager = new CanonicalMetaTagManager();

        self::assertTrue($manager->canHandleProperty('canonical'));
        self::assertTrue($manager->canHandleProperty('Canonical'));
        self::assertFalse($manager->canHandleProperty('description'));
    }

    #[Test]
    public function addPropertyThrowsExceptionForOtherProperties(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new CanonicalMetaTagManager()->addProperty('description', 'foo');
    }

    #[Test]
    public function rendersLinkTag(): void
    {
        $manager = new CanonicalMetaTagManager();
        $manager->addProperty('canonical', 'https://example.com/?a=1&b="2"');

        self::assertSame(
            '<link rel="canonical" href="https://example.com/?a=1&amp;b=&quot;2&quot;">',
            $manager->renderAllProperties()
        );
    }

    #[Test]
    public function rendersSelfClosingLinkTagForXmlCompliantDocType(): void
    {
        $manager = new CanonicalMetaTagManager();
        $manager->addProperty('canonical', 'https://example.com/');

        self::assertSame(
            '<link rel="canonical" href="https://example.com/"/>',
            $manager->renderAllProperties(DocType::xhtmlStrict)
        );
    }

    #[Test]
    public function addingPropertyReplacesPreviousUrl(): void
    {
        $manager = new CanonicalMetaTagManager();
        $manager->addProperty('canonical', 'https://example.com/first');
        $manager->addProperty('canonical', 'https://example.com/second');

        self::assertSame([['content' => 'https://example.com/second', 'subProperties' => []]], $manager->getProperty('canonical'));
    }

    #[Test]
    public function removePropertyRemovesUrl(): void
    {
        $manager = new CanonicalMetaTagManager();
        $manager->addProperty('canonical', 'https://example.com/');
        $manager->removeProperty('canonical');

        self::assertSame([], $manager->getProperty('canonical'));
        self::assertSame('', $manager->renderAllProperties());
    }

    #[Test]
    public function stateRestoresUrl(): void
    {
        $manager = new CanonicalMetaTagManager();
        $manager->addProperty('canonical', 'https://example.com/');
        $restoredManager = new CanonicalMetaTagManager();
        $restoredManager->updateState($manager->getState());

        self::assertSame('<link rel="canonical" href="https://example.com/">', $restoredManager->renderAllProperties());
    }
}
