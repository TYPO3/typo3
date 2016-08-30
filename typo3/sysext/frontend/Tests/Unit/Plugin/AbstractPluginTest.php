<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Plugin;

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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;

/**
 * Testcase for TYPO3\CMS\Frontend\Plugin\AbstractPlugin
 */
class AbstractPluginTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
     */
    protected $abstractPlugin;

    /**
     * @var array
     */
    protected $defaultPiVars;

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        parent::setUp();

        // Allow objects until 100 levels deep when executing the stdWrap
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObjectDepthCounter = 100;

        $this->abstractPlugin = new \TYPO3\CMS\Frontend\Plugin\AbstractPlugin();
        $contentObjectRenderer = new ContentObjectRenderer();
        $contentObjectRenderer->setContentObjectClassMap([
            'TEXT' => TextContentObject::class,
        ]);
        $this->abstractPlugin->cObj = $contentObjectRenderer;
        $this->defaultPiVars = $this->abstractPlugin->piVars;
    }

    /**
     * Data provider for piSetPiVarDefaultsStdWrap
     *
     * @return array input-array with configuration and stdWrap, expected output-array in piVars
     */
    public function piSetPiVarDefaultsStdWrapProvider()
    {
        return [
            'stdWrap on conf, non-recursive, stdWrap 1 level deep' => [
                [
                    'abc' => 'DEF',
                    'abc.' => [
                        'stdWrap.' => [
                            'wrap' => 'test | test'
                        ],
                    ],
                ],
                [
                    'abc' => 'testDEFtest',
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                ],
            ],
            'stdWrap on conf, non-recursive, stdWrap 2 levels deep' => [
                [
                    'xyz.' => [
                        'stdWrap.' => [
                            'cObject' => 'TEXT',
                            'cObject.' => [
                                'data' => 'date:U',
                                'strftime' => '%Y',
                            ],
                        ],
                    ],
                ],
                [
                    'xyz' => date('Y'),
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                ],
            ],
            'stdWrap on conf, recursive' => [
                [
                    'abc.' => [
                        'def' => 'DEF',
                        'def.' => [
                            'ghi' => '123',
                            'stdWrap.' => [
                                'wrap' => 'test | test'
                            ],
                        ],
                    ],
                ],
                [
                    'abc.' => [
                        'def' => 'testDEFtest',
                        'def.' => [
                            'ghi' => '123',
                        ],
                    ],
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider piSetPiVarDefaultsStdWrapProvider
     */
    public function piSetPiVarDefaultsStdWrap($input, $expected)
    {
        $this->abstractPlugin->piVars = $this->defaultPiVars;

        $this->abstractPlugin->conf['_DEFAULT_PI_VARS.'] = $input;
        $this->abstractPlugin->pi_setPiVarDefaults();
        $this->assertEquals($expected, $this->abstractPlugin->piVars);
    }
}
