<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\Loader;

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

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;

/**
 * Test case for the yaml file loader class
 */
class YamlFileLoaderTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Generic method to check if the load method returns an array from a YAML file
     * @test
     */
    public function load()
    {
        $fileName = 'Berta.yml';
        $fileContents = '
options:
    - option1
    - option2
betterthanbefore: 1
';

        $expected = [
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => 1
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents']);
        $subject->expects($this->once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        $this->assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     * @test
     */
    public function loadWithAnImport()
    {
        $fileName = 'Berta.yml';
        $fileContents = '
imports:
    - { resource: Secondfile.yml }

options:
    - option1
    - option2
betterthanbefore: 1
';

        $importFileName = 'Secondfile.yml';
        $importFileContents = '
options:
    - optionBefore
betterthanbefore: 2
';

        $expected = [
            'options' => [
                'optionBefore',
                'option1',
                'option2'
            ],
            'betterthanbefore' => 1
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents']);
        $subject->expects($this->at(0))->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $subject->expects($this->at(1))->method('getFileContents')->with($importFileName)->willReturn($importFileContents);
        $output = $subject->load($fileName);
        $this->assertSame($expected, $output);
    }

    /**
     * Method checking for placeholders
     * @test
     */
    public function loadWithPlacholders()
    {
        $fileName = 'Berta.yml';
        $fileContents = '

firstset:
  myinitialversion: 13
options:
    - option1
    - option2
betterthanbefore: %firstset.myinitialversion%
';

        $expected = [
            'firstset' => [
                'myinitialversion' => 13
            ],
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => 13
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents']);
        $subject->expects($this->once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        $this->assertSame($expected, $output);
    }

    /**
     * dataprovider for tests isPlaceholderTest
     * @return array
     */
    public function isPlaceholderDataProvider()
    {
        return [
            'regular string' => [
                'berta13',
                false
            ],
            'regular array' => [
                ['berta13'],
                false
            ],
            'regular float' => [
                13.131313,
                false
            ],
            'regular int' => [
                13,
                false
            ],
            'invalid placeholder with only % at the beginning' => [
                '%cool',
                false
            ],
            'invalid placeholder with only % at the end' => [
                'cool%',
                false
            ],
            'invalid placeholder with two % but not at the end' => [
                '%cool%again',
                false
            ],
            'invalid placeholder with two % but not at the beginning nor end' => [
                'did%you%know',
                false
            ],
            'valid placeholder with just numbers' => [
                '%13%',
                true
            ],
            'valid placeholder' => [
                '%foo%baracks%',
                true
            ],
        ];
    }

    /**
     * @dataProvider isPlaceholderDataProvider
     * @test
     * @param mixed $placeholderValue
     * @param bool $expected
     * @skip
     */
    public function isPlaceholderTest($placeholderValue, bool $expected)
    {
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['dummy']);
        $output = $subject->_call('isPlaceholder', $placeholderValue);
        $this->assertSame($expected, $output);
    }
}
