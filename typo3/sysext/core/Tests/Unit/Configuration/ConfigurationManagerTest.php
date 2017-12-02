<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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

/**
 * Test case
 */
class ConfigurationManagerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfigurationFileLocation',
                'getLocalConfigurationFileLocation',
            ]
        );
    }

    /**
     * @param array $methods
     */
    protected function createSubjectWithMockedMethods(array $methods)
    {
        $this->subject = $this->getMockBuilder(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1310203814);

        $defaultConfigurationFile = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('defaultConfiguration');
        file_put_contents(
            $defaultConfigurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203814); ?>'
        );
        $this->testFilesToDelete[] = $defaultConfigurationFile;

        $this->subject
            ->expects($this->once())
            ->method('getDefaultConfigurationFileLocation')
            ->will($this->returnValue($defaultConfigurationFile));
        $this->subject->getDefaultConfiguration();
    }

    /**
     * @test
     */
    public function getLocalConfigurationExecutesDefinedConfigurationFile()
    {
        $this->expectException(\RuntimeException::class);

        $configurationFile = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('localConfiguration');
        file_put_contents(
            $configurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203815); ?>'
        );
        $this->testFilesToDelete[] = $configurationFile;

        $this->subject
            ->expects($this->once())
            ->method('getLocalConfigurationFileLocation')
            ->will($this->returnValue($configurationFile));
        $this->subject->getLocalConfiguration();
    }

    /**
     * @test
     */
    public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray()
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'changed' => 'unChanged',
        ];
        $overrideConfiguration = [
            'changed' => 'changed',
            'new' => 'new'
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
        $this->subject->expects($this->once())
                ->method('getLocalConfiguration')
                ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
                ->method('writeLocalConfiguration')
                ->with($expectedConfiguration);

        $this->subject->updateLocalConfiguration($overrideConfiguration);
    }

    /**
     * @test
     */
    public function getDefaultConfigurationValueByPathReturnsCorrectValue()
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
            ]
        );
        $this->subject->expects($this->once())
                ->method('getDefaultConfiguration')
                ->will($this->returnValue(
                    [
                    'path' => 'value',
                ]
            ));

        $this->assertSame('value', $this->subject->getDefaultConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getLocalConfigurationValueByPathReturnsCorrectValue()
    {
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
                ->method('getLocalConfiguration')
                ->will($this->returnValue(
                    [
                    'path' => 'value',
                ]
            ));

        $this->assertSame('value', $this->subject->getLocalConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getConfigurationValueByPathReturnsCorrectValue()
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
                ->method('getDefaultConfiguration')
                ->will($this->returnValue(
                    [
                    'path' => 'value',
                ]
            ));
        $this->subject->expects($this->once())
                ->method('getLocalConfiguration')
                ->will($this->returnValue(
                    [
                    'path' => 'valueOverride',
                ]
            ));

        $this->assertSame('valueOverride', $this->subject->getConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid()
    {
        $this->createSubjectWithMockedMethods([
                'isValidLocalConfigurationPath',
            ]);
        $this->subject->expects($this->once())
                ->method('isValidLocalConfigurationPath')
                ->will($this->returnValue(false));

        $this->assertFalse($this->subject->setLocalConfigurationValueByPath('path', 'value'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath()
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
        $this->subject->expects($this->once())
                ->method('isValidLocalConfigurationPath')
                ->will($this->returnValue(true));
        $this->subject->expects($this->once())
                ->method('getLocalConfiguration')
                ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
                ->method('writeLocalConfiguration')
                ->with($expectedConfiguration);

        $this->subject->setLocalConfigurationValueByPath('toUpdate', 'updated');
    }

    /**
     * @test
     */
    public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs()
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
        $this->subject->expects($this->any())
                ->method('isValidLocalConfigurationPath')
                ->will($this->returnValue(true));
        $this->subject->expects($this->once())
                ->method('getLocalConfiguration')
                ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
                ->method('writeLocalConfiguration')
                ->with($expectedConfiguration);

        $pairs = [
            'toUpdate' => 'updated',
            'new' => 'new'
        ];
        $this->subject->setLocalConfigurationValuesByPathValuePairs($pairs);
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathRemovesGivenPathsFromConfigurationAndReturnsTrue()
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
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $removePaths = [
            'toRemove1',
            'toRemove2',
        ];
        $this->assertTrue($this->subject->removeLocalConfigurationKeysByPath($removePaths));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfNothingIsRemoved()
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
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->never())
            ->method('writeLocalConfiguration');

        $removeNothing = [];
        $this->assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNothing));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfSomethingInexistentIsRemoved()
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
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->never())
            ->method('writeLocalConfiguration');

        $removeNonExisting = ['notPresent'];
        $this->assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNonExisting));
    }

    /**
     * @test
     */
    public function canWriteConfigurationReturnsFalseIfLocalConfigurationFileIsNotWritable()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['dummy']);

        $file = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile = PATH_site . $file;
        touch($absoluteFile);
        $this->testFilesToDelete[] = $absoluteFile;
        chmod($absoluteFile, 0444);
        clearstatcache();

        $subject->_set('localConfigurationFile', $file);

        $result = $subject->canWriteConfiguration();

        chmod($absoluteFile, 0644);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function canWriteConfigurationReturnsTrueIfDirectoryAndFilesAreWritable()
    {
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['dummy']);

        $directory = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteDirectory = PATH_site . $directory;
        mkdir($absoluteDirectory);

        $file = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile1 = PATH_site . $file;
        touch($absoluteFile1);
        $this->testFilesToDelete[] = $absoluteFile1;
        $subject->_set('localConfigurationFile', $absoluteFile1);

        clearstatcache();

        $result = $subject->canWriteConfiguration();

        $this->assertTrue($result);
        $this->testFilesToDelete[] = $absoluteDirectory;
    }

    /**
     * @test
     */
    public function writeLocalConfigurationWritesSortedContentToConfigurationFile()
    {
        $configurationFile = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('localConfiguration');
        if (!is_file($configurationFile)) {
            if (!$fh = fopen($configurationFile, 'wb')) {
                $this->markTestSkipped('Can not create file ' . $configurationFile . '. Please check your write permissions.');
            }
            fclose($fh);
        }

        if (!@is_file($configurationFile)) {
            throw new \RuntimeException('File ' . $configurationFile . ' could not be found. Please check your write permissions', 1346364362);
        }
        $this->testFilesToDelete[] = $configurationFile;

        $this->subject
            ->expects($this->any())
            ->method('getLocalConfigurationFileLocation')
            ->will($this->returnValue($configurationFile));

        $pairs = [
            'foo' => 42,
            'bar' => 23
        ];
        $expectedContent =
            '<?php' . LF .
                'return [' . LF .
                    '    \'bar\' => 23,' . LF .
                    '    \'foo\' => 42,' . LF .
                '];' . LF;

        $this->subject->writeLocalConfiguration($pairs);
        $this->assertSame($expectedContent, file_get_contents($configurationFile));
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationThrowsExceptionIfFileExists()
    {
        $this->expectException(\RuntimeException::class);

        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['dummy']);

        $file = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile = PATH_site . $file;
        touch($absoluteFile);
        $this->testFilesToDelete[] = $absoluteFile;
        $subject->_set('localConfigurationFile', $file);

        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationWritesContentFromFactoryFile()
    {
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['writeLocalConfiguration']);
        $subject->_set('localConfigurationFile', 'typo3temp/var/tests/' . $this->getUniqueId('dummy_'));

        $factoryConfigurationFile = 'typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = PATH_site . $factoryConfigurationFile;
        $uniqueContentString = $this->getUniqueId('string_');
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
                'return [' . LF .
                    '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
                '];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $factoryConfigurationAbsoluteFile;

        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $subject
            ->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($this->arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationMergesConfigurationWithAdditionalFactoryFile()
    {
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['writeLocalConfiguration']);
        $subject->_set('localConfigurationFile', 'typo3temp/var/tests/' . $this->getUniqueId('dummy_'));

        $factoryConfigurationFile = 'typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = PATH_site . $factoryConfigurationFile;
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
                'return [];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $factoryConfigurationAbsoluteFile;
        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $additionalFactoryConfigurationFile = 'typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $additionalFactoryConfigurationAbsoluteFile = PATH_site . $additionalFactoryConfigurationFile;
        $uniqueContentString = $this->getUniqueId('string_');
        $validAdditionalFactoryConfigurationFileContent =
            '<?php' . LF .
                'return [' . LF .
                    '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
                '];' . LF;
        file_put_contents(
            $additionalFactoryConfigurationAbsoluteFile,
            $validAdditionalFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $additionalFactoryConfigurationAbsoluteFile;
        $subject->_set('additionalFactoryConfigurationFile', $additionalFactoryConfigurationFile);

        $subject
            ->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($this->arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathAcceptsWhitelistedPath()
    {
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['dummy']);
        $subject->_set('whiteListedLocalConfigurationPaths', ['foo/bar']);
        $this->assertTrue($subject->_call('isValidLocalConfigurationPath', 'foo/bar/baz'));
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathDeniesNotWhitelistedPath()
    {
        /** @var $subject \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, ['dummy']);
        $subject->_set('whiteListedLocalConfigurationPaths', ['foo/bar']);
        $this->assertFalse($subject->_call('isValidLocalConfigurationPath', 'bar/baz'));
    }
}
