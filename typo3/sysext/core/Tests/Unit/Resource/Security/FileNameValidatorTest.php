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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Security;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\StringUtility;

class FileNameValidatorTest extends TestCase
{
    /**
     * @return array
     */
    public function deniedFilesWithoutDenyPatternDataProvider(): array
    {
        return [
            'Nul character in file' => ['image' . "\0" . '.gif'],
            'Nul character in file with .php' => ['image.php' . "\0" . '.gif'],
            'Nul character and UTF-8 in file' => ['Ссылка' . "\0" . '.gif'],
            'Nul character and Latin-1 in file' => ['ÉÐØ' . "\0" . '.gif'],
        ];
    }

    /**
     * Tests whether validator detects files with nul character without file deny pattern.
     *
     * @param string $deniedFile
     * @test
     * @dataProvider deniedFilesWithoutDenyPatternDataProvider
     */
    public function verifyNulCharacterFilesAgainstPatternWithoutFileDenyPattern(string $deniedFile): void
    {
        $subject = new FileNameValidator('');
        self::assertFalse($subject->isValid($deniedFile));

        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '';
        $subject = new FileNameValidator();
        self::assertFalse($subject->isValid($deniedFile));
    }

    /**
     * @return array
     */
    public function deniedFilesWithDefaultDenyPatternDataProvider(): array
    {
        $data = [
            'Nul character in file' => ['image' . "\0", '.gif'],
            'Nul character in file with .php' => ['image.php' . "\0", '.gif'],
            'Nul character and UTF-8 in file' => ['Ссылка' . "\0", '.gif'],
            'Nul character and Latin-1 in file' => ['ÉÐØ' . "\0", '.gif'],
            'Lower umlaut .php file' => ['üWithFile', '.php'],
            'Upper umlaut .php file' => ['fileWithÜ', '.php'],
            'invalid UTF-8-sequence' => ["\xc0" . 'file', '.php'],
            'Could be overlong NUL in some UTF-8 implementations, invalid in RFC3629' => ["\xc0\x80" . 'file', '.php'],
            'Regular .php file' => ['file', '.php'],
            'Regular .php3 file' => ['file', '.php3'],
            'Regular .php5 file' => ['file', '.php5'],
            'Regular .php7 file' => ['file', '.php7'],
            'Regular .phpsh file' => ['file', '.phpsh'],
            'Regular .phtml file' => ['file', '.phtml'],
            'Regular .pht file' => ['file', '.pht'],
            'Regular .phar file' => ['file', '.phar'],
            'Regular .shtml file' => ['file', '.shtml'],
            'Regular .cgi file' => ['file', '.cgi'],
            'Regular .pl file' => ['file', '.pl'],
            'Wrapped .php file ' => ['file', '.php.txt'],
            'Wrapped .php3 file' => ['file', '.php3.txt'],
            'Wrapped .php5 file' => ['file', '.php5.txt'],
            'Wrapped .php7 file' => ['file', '.php7.txt'],
            'Wrapped .phpsh file' => ['file', '.phpsh.txt'],
            'Wrapped .phtml file' => ['file', '.phtml.txt'],
            'Wrapped .pht file' => ['file', '.pht.txt'],
            'Wrapped .phar file' => ['file', '.phar.txt'],
            'Wrapped .shtml file' => ['file', '.shtml.txt'],
            'Wrapped .cgi file' => ['file', '.cgi.txt'],
            // allowed "Wrapped .pl file" in order to allow language specific files containing ".pl."
            '.htaccess file' => ['', '.htaccess'],
        ];

        // Mixing with regular utf-8
        $utf8Characters = 'Ссылка';
        foreach ($data as $key => $value) {
            if ($value[0] === '') {
                continue;
            }
            $data[$key . ' with UTF-8 characters prepended'] = [$utf8Characters . $value[0], $value[1]];
            $data[$key . ' with UTF-8 characters appended'] = [$value[0] . $utf8Characters, $value[1]];
        }

        // combine to single value
        $data = array_map(
            function (array $values): array {
                return [implode('', $values)];
            },
            $data
        );

        // Encoding with UTF-16
        foreach ($data as $key => $value) {
            $data[$key . ' encoded with UTF-16'] = [mb_convert_encoding($value[0], 'UTF-16')];
        }

        return $data;
    }

    /**
     * Tests whether the basic FILE_DENY_PATTERN detects denied files.
     *
     * @param string $deniedFile
     * @test
     * @dataProvider deniedFilesWithDefaultDenyPatternDataProvider
     */
    public function isValidDetectsNotAllowedFiles(string $deniedFile): void
    {
        $subject = new FileNameValidator();
        self::assertFalse($subject->isValid($deniedFile));
    }

    /**
     * @return array
     */
    public function insecureFilesDataProvider(): array
    {
        return [
            'Classic php file' => ['user.php'],
            'A random .htaccess file' => ['.htaccess'],
            'Wrapped .php file' => ['file.php.txt'],
        ];
    }

    /**
     * @param string $fileName
     * @test
     * @dataProvider insecureFilesDataProvider
     */
    public function isValidAcceptsNotAllowedFilesDueToInsecureSetting(string $fileName): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '\\.phc$';
        $subject = new FileNameValidator();
        self::assertTrue($subject->isValid($fileName));
    }

    /**
     * @return array
     */
    public function allowedFilesDataProvider(): array
    {
        return [
            'Regular .gif file' => ['image.gif'],
            'Regular uppercase .gif file' => ['IMAGE.gif'],
            'UTF-8 .gif file' => ['Ссылка.gif'],
            'Lower umlaut .jpg file' => ['üWithFile.jpg'],
            'Upper umlaut .png file' => ['fileWithÜ.png'],
            'Latin-1 .gif file' => ['ÉÐØ.gif'],
            'Wrapped .pl file' => ['file.pl.txt'],
        ];
    }

    /**
     * Tests whether the basic file deny pattern accepts allowed files.
     *
     * @param string $allowedFile
     * @test
     * @dataProvider allowedFilesDataProvider
     */
    public function isValidAcceptAllowedFiles(string $allowedFile): void
    {
        $subject = new FileNameValidator();
        self::assertTrue($subject->isValid($allowedFile));
    }

    /**
     * @test
     */
    public function isCustomDenyPatternConfigured(): void
    {
        $subject = new FileNameValidator('nothing-really');
        self::assertTrue($subject->customFileDenyPatternConfigured());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = 'something-else';
        $subject = new FileNameValidator();
        self::assertTrue($subject->customFileDenyPatternConfigured());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = FileNameValidator::DEFAULT_FILE_DENY_PATTERN;
        $subject = new FileNameValidator();
        self::assertFalse($subject->customFileDenyPatternConfigured());
        $subject = new FileNameValidator(FileNameValidator::DEFAULT_FILE_DENY_PATTERN);
        self::assertFalse($subject->customFileDenyPatternConfigured());
    }

    /**
     * @test
     */
    public function customFileDenyPatternFindsMissingImportantParts(): void
    {
        $subject = new FileNameValidator('\\.php$|.php8$');
        self::assertTrue($subject->missingImportantPatterns());
        $subject = new FileNameValidator(FileNameValidator::DEFAULT_FILE_DENY_PATTERN);
        self::assertFalse($subject->missingImportantPatterns());
    }

    /**
     * Data provider for 'defaultFileDenyPatternMatchesPhpExtension' test case.
     *
     * @return array
     */
    public function phpExtensionDataProvider(): array
    {
        $data = [];
        $fileName = StringUtility::getUniqueId('filename');
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'phpsh', 'phtml', 'pht'];
        foreach ($phpExtensions as $extension) {
            $data[] = [$fileName . '.' . $extension];
            $data[] = [$fileName . '.' . $extension . '.txt'];
        }
        return $data;
    }

    /**
     * Tests whether an accordant PHP extension is denied.
     *
     * @test
     * @dataProvider phpExtensionDataProvider
     * @param string $fileName
     */
    public function defaultFileDenyPatternMatchesPhpExtension(string $fileName): void
    {
        self::assertGreaterThan(0, preg_match('/' . FileNameValidator::DEFAULT_FILE_DENY_PATTERN . '/', $fileName), $fileName);
    }

    /**
     * Tests whether an accordant PHP extension is denied.
     *
     * @test
     * @dataProvider phpExtensionDataProvider
     * @param string $fileName
     */
    public function invalidPhpExtensionIsDetected(string $fileName): void
    {
        $subject = new FileNameValidator();
        self::assertFalse($subject->isValid($fileName));
    }
}
