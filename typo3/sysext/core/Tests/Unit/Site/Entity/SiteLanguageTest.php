<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Site\Entity;

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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteLanguageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function toArrayReturnsProperOverlaidData()
    {
        $configuration = [
            'navigationTitle' => 'NavTitle',
            'customValue' => 'a custom value'
        ];
        $subject = new SiteLanguage(1, 'de', new Uri('/'), $configuration);
        $expected = [
            'languageId' => 1,
            'locale' => 'de',
            'base' => '/',
            'title' => 'Default',
            'navigationTitle' => 'NavTitle',
            'twoLetterIsoCode' => 'en',
            'hreflang' => 'en-US',
            'direction' => '',
            'typo3Language' => 'default',
            'flagIdentifier' => '',
            'fallbackType' => 'strict',
            'enabled' => true,
            'fallbackLanguageIds' => [],
            'customValue' => 'a custom value'
        ];
        $this->assertEquals($expected, $subject->toArray());
    }
}
