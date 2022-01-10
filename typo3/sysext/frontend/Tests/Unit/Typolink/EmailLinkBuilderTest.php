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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use TYPO3\CMS\Frontend\Typolink\EmailLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class EmailLinkBuilderTest extends UnitTestCase
{
    public function emailSpamProtectionWithTypeAsciiDataProvider(): array
    {
        return [
            'Simple email address' => [
                'test@email.tld',
                '&#116;&#101;&#115;&#116;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;',
            ],
            'Simple email address with unicode characters' => [
                'matthäus@email.tld',
                '&#109;&#97;&#116;&#116;&#104;&#228;&#117;&#115;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;',
            ],
            'Susceptible email address' => [
                '"><script>alert(\'emailSpamProtection\')</script>',
                '&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#101;&#109;&#97;&#105;&#108;&#83;&#112;&#97;&#109;&#80;&#114;&#111;&#116;&#101;&#99;&#116;&#105;&#111;&#110;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;',

            ],
            'Susceptible email address with unicode characters' => [
                '"><script>alert(\'ȅmǡilSpamProtȅction\')</script>',
                '&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#517;&#109;&#481;&#105;&#108;&#83;&#112;&#97;&#109;&#80;&#114;&#111;&#116;&#517;&#99;&#116;&#105;&#111;&#110;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;',
            ],
        ];
    }

    /**
     * Check if email spam protection processes all UTF-8 characters properly
     *
     * @test
     * @dataProvider emailSpamProtectionWithTypeAsciiDataProvider
     */
    public function mailSpamProtectionWithTypeAscii(string $content, string $expected): void
    {
        $subject = $this->getAccessibleMock(EmailLinkBuilder::class, ['dummy'], [], '', false);
        self::assertSame(
            $expected,
            $subject->_call('encryptEmail', $content, 'ascii')
        );
    }
}
