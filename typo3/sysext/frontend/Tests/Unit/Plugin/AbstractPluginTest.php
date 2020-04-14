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

namespace TYPO3\CMS\Frontend\Tests\Unit\Plugin;

use Prophecy\Argument;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use TYPO3\CMS\Frontend\Tests\Unit\Fixtures\ResultBrowserPluginHook;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractPluginTest extends UnitTestCase
{
    /**
     * @var AbstractPlugin
     */
    protected $abstractPlugin;

    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->cObjectDepthCounter = 100;
        $tsfe->getLanguage(Argument::cetera())->willReturn(
            $this->createSiteWithDefaultLanguage()->getLanguageById(0)
        );

        $this->abstractPlugin = new AbstractPlugin(null, $tsfe->reveal());
        $contentObjectRenderer = new ContentObjectRenderer($tsfe->reveal());
        $contentObjectRenderer->setContentObjectClassMap([
            'TEXT' => TextContentObject::class,
        ]);
        $this->abstractPlugin->cObj = $contentObjectRenderer;
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
                    'simplevalue' => 'lipsum'
                ],
                [
                    'abc' => 'testDEFtest',
                    'simplevalue' => 'lipsum',
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                    'a' => [
                        'bit' => 'nested'
                    ]
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
                    'a' => [
                        'bit' => 'nested'
                    ]
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
                    'simple_value' => '45'
                ],
                [
                    'abc' => [
                        'def' => [
                            'ghi' => '123',
                        ],
                    ],
                    'simple_value' => '45',
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                    'a' => [
                        'bit' => 'nested'
                    ]
                ],
            ],
            'stdWrap on conf, recursive, default pivars get overridden recursive nested set' => [
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
                    'a' => [
                        'default-is' => 'uncool'
                    ],
                    'simple_value' => '45'
                ],
                [
                    'abc' => [
                        'def' => [
                            'ghi' => '123',
                        ],
                    ],
                    'simple_value' => '45',
                    'pointer' => '',
                    'mode' => '',
                    'sword' => '',
                    'sort' => '',
                    'a' => [
                        'default-is' => 'uncool',
                        'bit' => 'nested'
                    ]
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
        $this->resetSingletonInstances = true;
        $this->abstractPlugin->piVars['a']['bit'] = 'nested';

        $this->abstractPlugin->conf['_DEFAULT_PI_VARS.'] = $input;
        $this->abstractPlugin->pi_setPiVarDefaults();
        self::assertEquals($expected, $this->abstractPlugin->piVars);
    }

    /**
     * Data provider for multiple registered result browser implementations
     *
     * @return array
     */
    public function registeredResultBrowserProvider()
    {
        return [
            'Result browser returning false' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => false,
                'expected' => ''
            ],
            'Result browser returning null' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => null,
                'expected' => ''
            ],
            'Result browser returning whitespace string' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => '   ',
                'expected' => ''
            ],
            'Result browser returning HTML' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => '<div><a href="index.php?id=1&pointer=1">1</a><a href="index.php?id=1&pointer=2">2</a><a href="index.php?id=1&pointer=3">3</a><a href="index.php?id=1&pointer=4">4</a></div>',
                'expected' => '<div><a href="index.php?id=1&pointer=1">1</a><a href="index.php?id=1&pointer=2">2</a><a href="index.php?id=1&pointer=3">3</a><a href="index.php?id=1&pointer=4">4</a></div>'
            ],
            'Result browser returning a truthy integer as string' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => '1',
                'expected' => '1'
            ],
            'Result browser returning a falsy integer' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => 0,
                'expected' => ''
            ],
            'Result browser returning a truthy integer' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => 1,
                'expected' => ''
            ],
            'Result browser returning a positive integer' => [
                'className' => StringUtility::getUniqueId('tx_coretest'),
                'returnValue' => 42,
                'expected' => ''
            ]
        ];
    }

    /**
     * @test
     * @dataProvider registeredResultBrowserProvider
     *
     * @param string $className
     * @param mixed $returnValue
     * @param string $expected
     */
    public function registeredResultBrowsersAreUsed($className, $returnValue, $expected)
    {
        $resultBrowserHook = $this->getMockBuilder(ResultBrowserPluginHook::class)
            ->setMockClassName($className)
            ->setMethods(['pi_list_browseresults'])
            ->disableOriginalConstructor()
            ->getMock();

        // Register hook mock object
        GeneralUtility::addInstance($className, $resultBrowserHook);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AbstractPlugin::class]['pi_list_browseresults'] = [$className];

        $resultBrowserHook->expects(self::atLeastOnce())
            ->method('pi_list_browseresults')
            ->with(1, '', [], 'pointer', true, false, $this->abstractPlugin)
            ->willReturn($returnValue);

        $actualReturnValue = $this->abstractPlugin->pi_list_browseresults();

        self::assertSame($expected, $actualReturnValue);

        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AbstractPlugin::class]['pi_list_browseresults']);
    }

    private function createSiteWithDefaultLanguage(): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 0,
                    'locale' => 'en_US',
                    'typo3Language' => 'en'
                ],
            ]
        ]);
    }
}
