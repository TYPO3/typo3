<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;

/**
 * Test case
 */
class YamlSourceTest extends UnitTestCase
{

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadNotExists()
    {
        $this->expectException(NoSuchFileException::class);
        $this->expectExceptionCode(1471473378);

        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
            'dummy',
        ], [], '', false);

        $input = [
            'EXT:form/Resources/Forms/_example.yaml'
        ];

        $mockYamlSource->_call('load', $input);
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadIsNotValidYamlUseSymfonyParser()
    {
        if (!extension_loaded('yaml')) {
            $this->expectException(ParseErrorException::class);
            $this->expectExceptionCode(1480195405);

            $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
                'dummy',
            ], [], '', false);

            $input = [
                'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Invalid.yaml'
            ];

            $mockYamlSource->_call('load', $input);
        }
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadIsNotValidYamlUsePhpExtensionParser()
    {
        if (extension_loaded('yaml')) {
            $this->expectException(ParseErrorException::class);
            $this->expectExceptionCode(1391894094);

            $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
                'dummy',
            ], [], '', false);

            $input = [
                'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Invalid.yaml'
            ];

            $mockYamlSource->_call('load', $input);
        }
    }

    /**
     * @test
     */
    public function getHeaderFromFileReturnsHeaderPart()
    {
        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
            'dummy',
        ], [], '', false);

        $input = GeneralUtility::getFileAbsFileName('EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Header.yaml');
        $expected =
'# Header 1
# Header 2
';

        $this->assertSame($expected, $mockYamlSource->_call('getHeaderFromFile', $input));
    }
}
