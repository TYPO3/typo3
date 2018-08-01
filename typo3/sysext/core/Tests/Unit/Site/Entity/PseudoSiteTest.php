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

use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PseudoSiteTest extends UnitTestCase
{

    /**
     * @return array
     */
    public function pseudoSiteReturnsProperEntryPointsDataProvider()
    {
        return [
            'no domain' => [
                [],
                ['/'],
                '/'
            ],
            'invalid domain argument' => [
                [
                    ['domain_name' => 'not.recognized.com']
                ],
                ['/'],
                '/'
            ],
            'regular domain given' => [
                [
                    ['domainName' => 'blog.example.com/download']
                ],
                ['//blog.example.com/download'],
                '//blog.example.com/download'
            ],
            'multiple domains given' => [
                [
                    ['domainName' => 'www.example.com'],
                    ['domainName' => 'blog.example.com'],
                    ['domainName' => 'blog.example.com/food-koma'],
                ],
                [
                    '//www.example.com',
                    '//blog.example.com',
                    '//blog.example.com/food-koma',
                ],
                '//www.example.com'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider pseudoSiteReturnsProperEntryPointsDataProvider
     */
    public function pseudoSiteReturnsProperEntryPoints($sysDomainRecords, $expectedResolvedEntryPoints, $expectedFirstEntryPoint)
    {
        $subject = new PseudoSite(13, ['domains' => $sysDomainRecords, 'languages' => []]);
        $this->assertSame($expectedResolvedEntryPoints, $subject->getEntryPoints());
        $this->assertSame($expectedFirstEntryPoint, $subject->getBase());
    }
}
