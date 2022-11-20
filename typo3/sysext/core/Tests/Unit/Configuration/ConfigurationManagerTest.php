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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager|MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfigurationFileLocation',
                'getSystemConfigurationFileLocation',
            ]
        );
    }

    /**
     * Helper method to create a random directory and return the path.
     * The path will be registered for deletion upon test ending
     *
     * @return string
     */
    protected function getTestDirectory(string $prefix = 'root_'): string
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId($prefix);
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        return $path;
    }

    protected function createSubjectWithMockedMethods(array $methods): void
    {
        $this->subject = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1310203814);

        $defaultConfigurationFile = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('defaultConfiguration');
        file_put_contents(
            $defaultConfigurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203814); ?>'
        );

        $this->subject
            ->expects(self::once())
            ->method('getDefaultConfigurationFileLocation')
            ->willReturn($defaultConfigurationFile);
        $this->subject->getDefaultConfiguration();
    }

    /**
     * @test
     */
    public function getSystemConfigurationExecutesDefinedConfigurationFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $configurationFile = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('localConfiguration');
        file_put_contents(
            $configurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203815); ?>'
        );

        $this->subject
            ->expects(self::once())
            ->method('getSystemConfigurationFileLocation')
            ->willReturn($configurationFile);
        $this->subject->getLocalConfiguration();
    }

    /**
     * @test
     */
    public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'changed' => 'unChanged',
        ];
        $overrideConfiguration = [
            'changed' => 'changed',
            'new' => 'new',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'changed' => 'changed',
            'new' => 'new',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $this->subject->updateLocalConfiguration($overrideConfiguration);
    }

    /**
     * @test
     */
    public function getDefaultConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getDefaultConfiguration')
            ->willReturn(
                [
                    'path' => 'value',
                ]
            );

        self::assertSame('value', $this->subject->getDefaultConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getLocalConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn(
                [
                    'path' => 'value',
                ]
            );

        self::assertSame('value', $this->subject->getLocalConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getDefaultConfiguration')
            ->willReturn(
                [
                    'path' => 'value',
                ]
            );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn(
                [
                    'path' => 'valueOverride',
                ]
            );

        self::assertSame('valueOverride', $this->subject->getConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid(): void
    {
        $this->createSubjectWithMockedMethods([
            'isValidLocalConfigurationPath',
        ]);
        $this->subject->expects(self::once())
            ->method('isValidLocalConfigurationPath')
            ->willReturn(false);

        self::assertFalse($this->subject->setLocalConfigurationValueByPath('path', 'value'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'notUpdated',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'updated',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'isValidLocalConfigurationPath',
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('isValidLocalConfigurationPath')
            ->willReturn(true);
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $this->subject->setLocalConfigurationValueByPath('toUpdate', 'updated');
    }

    /**
     * @test
     */
    public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'notUpdated',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'updated',
            'new' => 'new',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'isValidLocalConfigurationPath',
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject
            ->method('isValidLocalConfigurationPath')
            ->willReturn(true);
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $pairs = [
            'toUpdate' => 'updated',
            'new' => 'new',
        ];
        $this->subject->setLocalConfigurationValuesByPathValuePairs($pairs);
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathRemovesGivenPathsFromConfigurationAndReturnsTrue(): void
    {
        $currentLocalConfiguration = [
            'toRemove1' => 'foo',
            'notChanged' => 23,
            'toRemove2' => 'bar',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
        ];

        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $removePaths = [
            'toRemove1',
            'toRemove2',
        ];
        self::assertTrue($this->subject->removeLocalConfigurationKeysByPath($removePaths));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfNothingIsRemoved(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
        ];
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::never())
            ->method('writeLocalConfiguration');

        $removeNothing = [];
        self::assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNothing));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfSomethingInexistentIsRemoved(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
        ];
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects(self::once())
            ->method('getLocalConfiguration')
            ->willReturn($currentLocalConfiguration);
        $this->subject->expects(self::never())
            ->method('writeLocalConfiguration');

        $removeNonExisting = ['notPresent'];
        self::assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNonExisting));
    }

    /**
     * @test
     */
    public function canWriteConfigurationReturnsTrueIfDirectoryAndFilesAreWritable(): void
    {
        $directory = StringUtility::getUniqueId('test_');
        $absoluteDirectory = $this->getTestDirectory() . '/' . $directory;
        mkdir($absoluteDirectory);

        $file = StringUtility::getUniqueId('test_');
        $absoluteFile1 = $absoluteDirectory . '/' . $file;
        touch($absoluteFile1);
        clearstatcache();
        $this->subject
            ->method('getSystemConfigurationFileLocation')
            ->willReturn($absoluteFile1);

        $result = $this->subject->canWriteConfiguration();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function writeLocalConfigurationWritesSortedContentToConfigurationFile(): void
    {
        $configurationFile = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('localConfiguration');
        if (!is_file($configurationFile)) {
            if (!$fh = fopen($configurationFile, 'wb')) {
                self::markTestSkipped('Can not create file ' . $configurationFile . '. Please check your write permissions.');
            }
            fclose($fh);
        }

        if (!@is_file($configurationFile)) {
            throw new \RuntimeException(
                'File ' . $configurationFile . ' could not be found. Please check your write permissions',
                1346364362
            );
        }

        $this->subject
            ->method('getSystemConfigurationFileLocation')
            ->willReturn($configurationFile);

        $pairs = [
            'foo' => 42,
            'bar' => 23,
        ];
        $expectedContent =
            '<?php' . LF .
            'return [' . LF .
            '    \'bar\' => 23,' . LF .
            '    \'foo\' => 42,' . LF .
            '];' . LF;

        $this->subject->writeLocalConfiguration($pairs);
        self::assertSame($expectedContent, file_get_contents($configurationFile));
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationThrowsExceptionIfFileExists(): void
    {
        $this->expectException(\RuntimeException::class);

        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['getSystemConfigurationFileLocation']);

        $file = StringUtility::getUniqueId('test_');
        $absoluteFile = $this->getTestDirectory() . '/' . $file;
        touch($absoluteFile);
        $subject->method('getSystemConfigurationFileLocation')->willReturn($absoluteFile);
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationWritesContentFromFactoryFile(): void
    {
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['writeLocalConfiguration', 'getSystemConfigurationFileLocation', 'getFactoryConfigurationFileLocation']);
        $localConfigurationFile = '/tests/' . StringUtility::getUniqueId('dummy_');
        $subject->method('getSystemConfigurationFileLocation')->willReturn(Environment::getVarPath() . '/' . $localConfigurationFile);

        $factoryConfigurationFile = StringUtility::getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = $this->getTestDirectory() . '/' . $factoryConfigurationFile;
        $subject->method('getFactoryConfigurationFileLocation')->willReturn($factoryConfigurationAbsoluteFile);
        $uniqueContentString = StringUtility::getUniqueId('string_');
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [' . LF .
            '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
            '];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );

        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $subject
            ->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with(self::arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationMergesConfigurationWithAdditionalFactoryFile(): void
    {
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['writeLocalConfiguration', 'getSystemConfigurationFileLocation', 'getFactoryConfigurationFileLocation', 'getAdditionalFactoryConfigurationFileLocation']);
        $testDirectory = $this->getTestDirectory() . '/';
        $localConfigurationFile = $testDirectory . StringUtility::getUniqueId('dummy_');
        $subject->method('getSystemConfigurationFileLocation')->willReturn($localConfigurationFile);

        $factoryConfigurationFile =  StringUtility::getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = $testDirectory . $factoryConfigurationFile;
        $subject->method('getFactoryConfigurationFileLocation')->willReturn($factoryConfigurationAbsoluteFile);
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );
        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $additionalFactoryConfigurationFile = StringUtility::getUniqueId('test_') . '.php';
        $additionalFactoryConfigurationAbsoluteFile = $testDirectory . $additionalFactoryConfigurationFile;
        $subject->method('getAdditionalFactoryConfigurationFileLocation')->willReturn($additionalFactoryConfigurationAbsoluteFile);
        $uniqueContentString = StringUtility::getUniqueId('string_');
        $validAdditionalFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [' . LF .
            '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
            '];' . LF;
        file_put_contents(
            $additionalFactoryConfigurationAbsoluteFile,
            $validAdditionalFactoryConfigurationFileContent
        );
        $subject->_set('additionalFactoryConfigurationFile', $additionalFactoryConfigurationFile);

        $subject
            ->expects(self::once())
            ->method('writeLocalConfiguration')
            ->with(self::arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathAcceptsAllowedPath(): void
    {
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);
        $subject->_set('allowedSettingsPaths', ['foo/bar']);
        self::assertTrue($subject->_call('isValidLocalConfigurationPath', 'foo/bar/baz'));
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathDeniesNotAllowedPath(): void
    {
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);
        $subject->_set('allowedSettingsPaths', ['foo/bar']);
        self::assertFalse($subject->_call('isValidLocalConfigurationPath', 'bar/baz'));
    }
}
