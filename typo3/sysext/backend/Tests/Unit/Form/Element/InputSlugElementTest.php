<?php
declare(strict_types=1);
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\InputSlugElement;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for InputSlugElement Form
 */
class InputSlugElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getPrefixReturnsDefaultBaseUrlForAllDefinedLanguagesAndMinusOne(): void
    {
        $languages = [
            [
                'languageId' => 0,
                'locale' => 'en_US.UTF-8',
                'base' => '/en/'
            ],
            [
                'languageId' => 1,
                'locale' => 'de_DE.UTF-8',
                'base' => '/de/'
            ]
        ];

        $site = new Site('www.foo.de', 0, [
            'languages' => $languages
        ]);

        $subject = $this->getAccessibleMock(
            InputSlugElement::class,
            ['dummy'],
            [],
            '',
            false
        );

        self::assertSame('/en', $subject->_call('getPrefix', $site, -1));
        self::assertSame('/en', $subject->_call('getPrefix', $site, 0));
        self::assertSame('/de', $subject->_call('getPrefix', $site, 1));
    }
}
