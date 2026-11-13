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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Enum\UnrecognizedExtensionFolderReason;
use TYPO3\CMS\Extensionmanager\Report\ExtensionStatus;
use TYPO3\CMS\Extensionmanager\Service\UnrecognizedExtensionFolderFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UnrecognizedExtensionFolderFinderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reports',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extensionmanager/Tests/Functional/Fixtures/Extensions/deepl_base',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $extensionsPath = Environment::getExtensionsPath() . '/';
        GeneralUtility::mkdir_deep($extensionsPath . 'without_composer_json');
        GeneralUtility::writeFile($extensionsPath . 'without_composer_json/ext_emconf.php', "<?php\n\$EM_CONF[\$_EXTKEY] = [];\n");
        GeneralUtility::mkdir_deep($extensionsPath . 'invalid_composer_json');
        GeneralUtility::writeFile($extensionsPath . 'invalid_composer_json/composer.json', '{"name": ');
        GeneralUtility::mkdir_deep($extensionsPath . 'library_package');
        GeneralUtility::writeFile($extensionsPath . 'library_package/composer.json', '{"name": "vendor/library", "type": "library"}');
        GeneralUtility::mkdir_deep($extensionsPath . 'without_extension_key');
        GeneralUtility::writeFile($extensionsPath . 'without_extension_key/composer.json', '{"name": "vendor/without-extension-key", "type": "typo3-cms-extension"}');
        GeneralUtility::writeFile($extensionsPath . 'some_file.txt', 'not a folder');
        GeneralUtility::mkdir_deep($extensionsPath . '.hidden_folder');
    }

    #[Test]
    public function findReturnsFoldersNotRegisteredAsExtensionWithReason(): void
    {
        self::assertSame(
            [
                'invalid_composer_json' => UnrecognizedExtensionFolderReason::InvalidComposerJson,
                'library_package' => UnrecognizedExtensionFolderReason::NoTypo3PackageType,
                'without_composer_json' => UnrecognizedExtensionFolderReason::MissingComposerJson,
                'without_extension_key' => UnrecognizedExtensionFolderReason::MissingExtensionKey,
            ],
            $this->get(UnrecognizedExtensionFolderFinder::class)->find()
        );
    }

    #[Test]
    public function extensionStatusReportsUnrecognizedFoldersAsWarning(): void
    {
        $status = $this->get(ExtensionStatus::class)->getStatus()['unrecognizedExtensionFolders'];
        self::assertSame(ContextualFeedbackSeverity::WARNING, $status->getSeverity());
        self::assertStringContainsString('without_composer_json', $status->getMessage());
        self::assertStringContainsString('no composer.json', $status->getMessage());
        self::assertStringNotContainsString('deepl_base', $status->getMessage());
    }
}
