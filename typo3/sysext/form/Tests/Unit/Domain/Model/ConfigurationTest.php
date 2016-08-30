<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain;

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
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository;

/**
 * Test case for class \TYPO3\CMS\Form\Domain\Model\Configuration
 */
class ConfigurationTest extends UnitTestCase
{
    /**
     * @var Configuration
     */
    protected $subject = null;

    /*
     * @var TypoScriptRepository|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $typoScriptRepositoryProphecy;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->typoScriptRepositoryProphecy = $this->prophesize(TypoScriptRepository::class);
        $this->subject = $this->getAccessibleMock(Configuration::class, ['__none']);
        $this->subject->_set('typoScriptRepository', $this->typoScriptRepositoryProphecy->reveal());
    }

    /**
     * Tears down this test case.
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->typoScriptRepositoryProphecy);
        unset($this->subject);
    }

    /**
     * @param array $typoScript
     * @param bool $globalCompatibilityMode
     * @param string $globalThemeName
     * @param array $expected
     *
     * @test
     * @dataProvider propertiesAreUpdatedFromTypoScriptDataProvider
     */
    public function propertiesAreUpdatedFromTypoScript(array $typoScript, $globalCompatibilityMode, $globalThemeName, array $expected)
    {
        $this->typoScriptRepositoryProphecy
            ->getModelConfigurationByScope('FORM', 'compatibilityMode')
            ->willReturn($globalCompatibilityMode);

        $this->typoScriptRepositoryProphecy
            ->getModelConfigurationByScope('FORM', 'themeName')
            ->willReturn($globalThemeName);

        $this->subject->setTypoScript($typoScript);
        $this->assertEquals($expected['prefix'], $this->subject->getPrefix());
        $this->assertEquals($expected['compatibility'], $this->subject->getCompatibility());
        $this->assertEquals($expected['contentElementRendering'], $this->subject->getContentElementRendering());
    }

    /**
     * @return array
     */
    public function propertiesAreUpdatedFromTypoScriptDataProvider()
    {
        return [
            '#1' => [
                [
                    'prefix' => '',
                    'themeName' => '',
                    'compatibilityMode' => false,
                    'disableContentElement' => false,
                ],
                false,
                '',
                [
                    'prefix' => 'form',
                    'themeName' => 'Default',
                    'compatibility' => false,
                    'contentElementRendering' => true,
                ],
            ],
            '#2' => [
                [
                    'prefix' => '',
                    'themeName' => '',
                    'compatibilityMode' => false,
                    'disableContentElement' => false,
                ],
                true,
                '',
                [
                    'prefix' => 'form',
                    'themeName' => 'Default',
                    'compatibility' => false,
                    'contentElementRendering' => true,
                ],
            ],
            '#3' => [
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibilityMode' => true,
                    'disableContentElement' => true,
                ],
                true,
                '',
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibility' => true,
                    'contentElementRendering' => false,
                ],
            ],
            '#4' => [
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibilityMode' => true,
                    'disableContentElement' => true,
                ],
                false,
                '',
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibility' => true,
                    'contentElementRendering' => false,
                ],
            ],
            '#5' => [
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibilityMode' => null,
                    'disableContentElement' => true,
                ],
                true,
                '',
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibility' => true,
                    'contentElementRendering' => false,
                ],
            ],
            '#6' => [
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibilityMode' => null,
                    'disableContentElement' => true,
                ],
                false,
                '',
                [
                    'prefix' => 'somePrefix',
                    'themeName' => 'someTheme',
                    'compatibility' => false,
                    'contentElementRendering' => false,
                ],
            ],
            '#7' => [
                [
                    'prefix' => '',
                    'themeName' => '',
                    'compatibilityMode' => false,
                    'disableContentElement' => false,
                ],
                false,
                'globalTheme',
                [
                    'prefix' => 'form',
                    'themeName' => 'globalTheme',
                    'compatibility' => false,
                    'contentElementRendering' => true,
                ],
            ],
        ];
    }
}
