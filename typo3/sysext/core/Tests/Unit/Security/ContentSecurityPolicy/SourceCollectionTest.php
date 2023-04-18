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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SourceCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canExtend(): void
    {
        $subject = new SourceCollection();
        $schemeItem = SourceScheme::data;
        $firstUriItem = new UriValue('https://example.org');
        $secondUriItem = new UriValue('https://example.org');
        $subject = $subject->with($schemeItem, $schemeItem, $firstUriItem, $secondUriItem);
        // $schemeItem (same instance) and `$uriItem` (different instances,
        // but same internal value) shall only be there once
        self::assertSame([$schemeItem, $firstUriItem], $subject->sources);
    }

    /**
     * @test
     */
    public function canReduce(): void
    {
        $subject = new SourceCollection();
        $schemeItem = SourceScheme::data;
        $firstUriItem = new UriValue('https://example.org');
        $secondUriItem = new UriValue('https://example.org');
        $subject = $subject->with($schemeItem, $firstUriItem);
        $subject = $subject->without($schemeItem, $secondUriItem);
        self::assertSame([], $subject->sources);
    }
}
