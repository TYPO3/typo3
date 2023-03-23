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

namespace TYPO3\CMS\Core\Tests\Unit\Html;

use TYPO3\CMS\Core\Html\HtmlCropper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HtmlCropperTest extends UnitTestCase
{
    private HtmlCropper $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HtmlCropper();
    }

    /**
     * @test
     */
    public function cropIsMultibyteSafe(): void
    {
        $actual = $this->subject->crop(
            content: 'бла',
            numberOfChars: 3,
            replacementForEllipsis: '...',
            cropToSpace: false
        );
        self::assertEquals('бла', $actual);
    }

    public static function cropWorksDataProvicer(): \Generator
    {
        $plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248)
            . 'j implemented the original version of the crop function.';
        $textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
            . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the '
            . 'original version of the crop function.';
        $textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; '
            . 'original version of the crop function.';
        $textWithLinebreaks = "Lorem ipsum dolor sit amet,\n"
            . "consetetur sadipscing elitr,\n"
            . 'sed diam nonumy eirmod tempor invidunt ut labore e'
            . 't dolore magna aliquyam';
        $textWith2000Chars = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vesti&amp;';
        $textWith1000AmpHtmlEntity = str_repeat('&amp;', 1000);
        $textWith2000AmpHtmlEntity = str_repeat('&amp;', 2000);

        // plain text
        yield 'plain text; 11|...|0' => [
            'expected' => 'Kasper Sk' . chr(229) . 'r...',
            'content' => $plainText,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'plain text; -58|...|0' => [
            'expected' => '...h' . chr(248) . 'j implemented the original version of the crop function.',
            'content' => $plainText,
            'numberOfChars' => -58,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'plain text; 4|...|1' => [
            'expected' => 'Kasp...',
            'content' => $plainText,
            'numberOfChars' => 4,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; 20|...|1' => [
            'expected' => 'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
            'content' => $plainText,
            'numberOfChars' => 20,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; -5|...|1' => [
            'expected' => '...tion.',
            'content' => $plainText,
            'numberOfChars' => -5,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; -49|...|1' => [
            'expected' => '...the original version of the crop function.',
            'content' => $plainText,
            'numberOfChars' => -49,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        // with markup (html)
        yield 'text with markup; 11|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'r...</a></strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup; 13|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . '...</a></strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 13,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup; 14|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 14,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup; 15|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 15,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup; 29|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> th...',
            'content' => $textWithMarkup,
            'numberOfChars' => 29,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup; -58|...|0' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">...h' . chr(248)
                . 'j</a> implemented</strong> the original version of the crop function.',
            'content' => $textWithMarkup,
            'numberOfChars' => -58,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with markup 4|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasp...</a></strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 4,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; 11|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; 13|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 13,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; 14|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 14,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; 15|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
            'content' => $textWithMarkup,
            'numberOfChars' => 15,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; 29|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...',
            'content' => $textWithMarkup,
            'numberOfChars' => 29,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with markup; -66|...|1' => [
            'expected' => '<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229)
                . 'rh' . chr(248) . 'j</a> implemented</strong> the original v'
                . 'ersion of the crop function.',
            'content' => $textWithMarkup,
            'numberOfChars' => -66,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        // text with ententies (html)
        yield 'text with entities 9|...|0' => [
            'expected' => 'Kasper Sk...',
            'content' => $textWithEntities,
            'numberOfChars' => 9,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 10|...|0' => [
            'expected' => 'Kasper Sk&aring;...',
            'content' => $textWithEntities,
            'numberOfChars' => 10,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 11|...|0' => [
            'expected' => 'Kasper Sk&aring;r...',
            'content' => $textWithEntities,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 13|...|0' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;...',
            'content' => $textWithEntities,
            'numberOfChars' => 13,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 14|...|0' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j...',
            'content' => $textWithEntities,
            'numberOfChars' => 14,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 15|...|0' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j ...',
            'content' => $textWithEntities,
            'numberOfChars' => 15,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 16|...|0' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j i...',
            'content' => $textWithEntities,
            'numberOfChars' => 16,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities -57|...|0' => [
            'expected' => '...j implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -57,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities -58|...|0' => [
            'expected' => '...&oslash;j implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -58,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities -59|...|0' => [
            'expected' => '...h&oslash;j implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -59,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'text with entities 4|...|1' => [
            'expected' => 'Kasp...',
            'content' => $textWithEntities,
            'numberOfChars' => 4,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 9|...|1' => [
            'expected' => 'Kasper...',
            'content' => $textWithEntities,
            'numberOfChars' => 9,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 10|...|1' => [
            'expected' => 'Kasper...',
            'content' => $textWithEntities,
            'numberOfChars' => 10,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 11|...|1' => [
            'expected' => 'Kasper...',
            'content' => $textWithEntities,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 13|...|1' => [
            'expected' => 'Kasper...',
            'content' => $textWithEntities,
            'numberOfChars' => 13,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 14|...|1' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j...',
            'content' => $textWithEntities,
            'numberOfChars' => 14,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 15|...|1' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j...',
            'content' => $textWithEntities,
            'numberOfChars' => 15,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities 16|...|1' => [
            'expected' => 'Kasper Sk&aring;rh&oslash;j...',
            'content' => $textWithEntities,
            'numberOfChars' => 16,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities -57|...|1' => [
            'expected' => '...implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -57,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities -58|...|1' => [
            'expected' => '...implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -58,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'text with entities -59|...|1' => [
            'expected' => '...implemented the; original version of the crop function.',
            'content' => $textWithEntities,
            'numberOfChars' => -59,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        // some tests without prepared data
        yield 'text with dash in html-element 28|...|1' => [
            'expected' => 'Some text with a link to <link email.address@example.org - '
                . 'mail "Open email window">my...</link>',
            'content' => 'Some text with a link to <link email.address@example.org - m'
                . 'ail "Open email window">my email.address@example.org<'
                . '/link> and text after it',
            'numberOfChars' => 28,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'html elements with dashes in attributes' => [
            'expected' => '<em data-foo="x">foobar</em>foo',
            'content' => '<em data-foo="x">foobar</em>foo',
            'numberOfChars' => 9,
            'replacementForEllipsis' => '',
            'cropToSpace' => false,
        ];

        yield 'html elements with iframe embedded 24|...|1' => [
            'expected' => 'Text with iframe <iframe src="//what.ever/"></iframe> and...',
            'content' => 'Text with iframe <iframe src="//what.ever/">'
                . '</iframe> and text after it',
            'numberOfChars' => 24,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'html elements with script tag embedded 24|...|1' => [
            'expected' => 'Text with script <script>alert(\'foo\');</script> and...',
            'content' => 'Text with script <script>alert(\'foo\');</script> '
                . 'and text after it',
            'numberOfChars' => 24,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        // text with linebreaks
        yield 'text with linebreaks' => [
            'expected' => "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\ns"
                . 'ed diam nonumy eirmod tempor invidunt ut labore e'
                . 't dolore magna',
            'content' => $textWithLinebreaks,
            'numberOfChars' => 121,
            'replacementForEllipsis' => '',
            'cropToSpace' => false,
        ];

        // long texts
        yield 'long text under the crop limit' => [
            'expected' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit' . ' ...',
            'content' => $textWith2000Chars,
            'numberOfChars' => 962,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'long text above the crop limit' => [
            'expected' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. N' . '...',
            'content' => $textWith2000Chars,
            'numberOfChars' => 1000,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'long text above the crop limit #2' => [
            'expected' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vesti&amp;' . '...',
            'content' => $textWith2000Chars . $textWith2000Chars,
            'numberOfChars' => 2000,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        // ensure that large number of html entities do not break the regexp splitting
        yield 'long text with large number of html entities' => [
            'expected' => $textWith1000AmpHtmlEntity . '...',
            'content' => $textWith2000AmpHtmlEntity,
            'numberOfChars' => 1000,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];
    }

    /**
     * @test
     * @dataProvider cropWorksDataProvicer
     */
    public function cropWorks(string $expected, string $content, int $numberOfChars, string $replacementForEllipsis, bool $cropToSpace): void
    {
        $this->handleCharset($content, $expected);
        self::assertEquals($expected, $this->subject->crop(
            content: $content,
            numberOfChars: $numberOfChars,
            replacementForEllipsis: $replacementForEllipsis,
            cropToSpace: $cropToSpace
        ));
    }

    /**
     * Converts the subject and the expected result into utf-8.
     *
     * @param string $subject the subject, will be modified
     * @param string $expected the expected result, will be modified
     */
    protected function handleCharset(string &$subject, string &$expected): void
    {
        $subject = mb_convert_encoding($subject, 'utf-8', 'iso-8859-1');
        $expected = mb_convert_encoding($expected, 'utf-8', 'iso-8859-1');
    }
}
