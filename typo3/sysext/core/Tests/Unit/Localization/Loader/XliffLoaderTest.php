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

namespace TYPO3\CMS\Core\Tests\Unit\Localization\Loader;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\Loader\XliffLoader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class XliffLoaderTest extends UnitTestCase
{
    public static function canLoadXliffDataProvider(): \Generator
    {
        yield 'Can load default language' => [
            'languageKey' => 'en',
            'fixture' => 'locallang.xlf',
            'expectedMessages' => [
                'label1' => 'This is label #1',
                'label2' => 'This is label #2',
                'label3' => 'This is label #3',
            ],
            'requireApprovedLocalizations' => false,
        ];
        yield 'Can load French translation with approved only' => [
            'languageKey' => 'fr',
            'fixture' => 'fr.locallang.xlf',
            'expectedMessages' => [
                'label1' => 'Ceci est le libellé no. 1',
                'label2' => 'Ceci est le libellé no. 2 [approved]',
            ],
            'requireApprovedLocalizations' => true,
        ];
        yield 'Can load French translation with non-approved' => [
            'languageKey' => 'fr',
            'fixture' => 'fr.locallang.xlf',
            'expectedMessages' => [
                'label1' => 'Ceci est le libellé no. 1',
                'label2' => 'Ceci est le libellé no. 2 [approved]',
                'label3' => 'Ceci est le libellé no. 3 [not approved]',
            ],
            'requireApprovedLocalizations' => false,
        ];
    }

    #[DataProvider('canLoadXliffDataProvider')]
    #[Test]
    public function canLoadXliff(string $languageKey, string $fixture, array $expectedMessages, bool $requireApprovedLocalizations): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations'] = $requireApprovedLocalizations;

        $fixturePath = __DIR__ . '/Fixtures/' . $fixture;
        $subject = new XliffLoader();
        $catalogue = $subject->load($fixturePath, $languageKey);

        self::assertEquals($languageKey, $catalogue->getLocale());

        $messages = $catalogue->all('messages');

        foreach ($expectedMessages as $key => $expectedValue) {
            self::assertArrayHasKey($key, $messages, sprintf('Message key "%s" not found', $key));
            self::assertEquals($expectedValue, $messages[$key], sprintf('Message value for "%s" does not match', $key));
        }

        // Ensure no unexpected messages are present
        foreach ($messages as $key => $value) {
            self::assertArrayHasKey($key, $expectedMessages, sprintf('Unexpected message key "%s" found', $key));
        }
    }

    #[Test]
    public function throwsExceptionForNonExistentFile(): void
    {
        $this->expectException(\TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException::class);

        $subject = new XliffLoader();
        $subject->load('/non/existent/file.xlf', 'en');
    }

    #[Test]
    public function returnsEmptyCatalogueForInvalidXml(): void
    {
        $this->expectException(\TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException::class);

        // Create a temporary invalid XML file
        $tempFile = tempnam(sys_get_temp_dir(), 'invalid_xlf_');
        file_put_contents($tempFile, '<?xml version="1.0"?><invalid><unclosed>');

        try {
            $subject = new XliffLoader();
            $subject->load($tempFile, 'en');
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function canHandleDefaultLanguageWithoutTargets(): void
    {
        $fixturePath = __DIR__ . '/Fixtures/locallang.xlf';
        $subject = new XliffLoader();
        $catalogue = $subject->load($fixturePath, 'en');

        $messages = $catalogue->all('messages');

        // For default language, source should be used as target
        self::assertEquals('This is label #1', $messages['label1']);
        self::assertEquals('This is label #2', $messages['label2']);
        self::assertEquals('This is label #3', $messages['label3']);
    }

    #[Test]
    public function respectsApprovalSettings(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations'] = true;

        $fixturePath = __DIR__ . '/Fixtures/fr.locallang.xlf';
        $subject = new XliffLoader();
        $catalogue = $subject->load($fixturePath, 'fr');

        $messages = $catalogue->all('messages');

        // Only approved translation should be present or not explicitly approved
        self::assertArrayHasKey('label1', $messages);
        self::assertArrayHasKey('label2', $messages);
        self::assertArrayNotHasKey('label3', $messages); // explicitly not approved
    }
}
