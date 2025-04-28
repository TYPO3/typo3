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

namespace TYPO3\CMS\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FlexFormServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function convertFlexFormContentToArrayResolvesComplexArrayStructure(): void
    {
        $input = '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>
<T3FlexForms>
	<data>
		<sheet index="sDEF">
			<language index="lDEF">
				<field index="settings.foo">
					<value index="vDEF">Foo-Value</value>
				</field>
				<field index="settings.bar">
					<el index="el">
						<section index="1">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz1-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum1-Value</value>
									</field>
									<field index="dot.one">
										<value index="vDEF">dot.one-Value</value>
									</field>
									<field index="dot.two">
										<value index="vDEF">dot.two-Value</value>
									</field>
								</el>
							</itemType>
						</section>
						<section index="2">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz2-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum2-Value</value>
									</field>
								</el>
							</itemType>
						</section>
					</el>
				</field>
			</language>
		</sheet>
	</data>
</T3FlexForms>';

        $expected = [
            'settings' => [
                'foo' => 'Foo-Value',
                'bar' => [
                    1 => [
                        'baz' => 'Baz1-Value',
                        'bum' => 'Bum1-Value',
                        'dot' => [
                            'one' => 'dot.one-Value',
                            'two' => 'dot.two-Value',
                        ],
                    ],
                    2 => [
                        'baz' => 'Baz2-Value',
                        'bum' => 'Bum2-Value',
                    ],
                ],
            ],
        ];

        // The subject calls xml2array statically, which calls a runtime cache, this need to be mocked.
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->willReturn($cacheMock);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $flexFormService = new FlexFormService();
        $convertedFlexFormArray = $flexFormService->convertFlexFormContentToArray($input);
        self::assertSame($expected, $convertedFlexFormArray);
    }

    #[Test]
    public function convertFlexFormContentToArrayResolvesMultipleSheets(): void
    {
        $input = '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>
<T3FlexForms>
	<data>
		<sheet index="sDEF">
			<language index="lDEF">
				<field index="settings.foo">
					<value index="vDEF">Foo-Value</value>
				</field>
				<field index="settings.bar">
					<el index="el">
						<section index="1">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz1-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum1-Value</value>
									</field>
									<field index="dot.one">
										<value index="vDEF">dot.one-Value</value>
									</field>
									<field index="dot.two">
										<value index="vDEF">dot.two-Value</value>
									</field>
								</el>
							</itemType>
						</section>
						<section index="2">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz2-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum2-Value</value>
									</field>
								</el>
							</itemType>
						</section>
					</el>
				</field>
			</language>
		</sheet>
		<sheet index="sheet2">
			<language index="lDEF">
				<field index="settings.foo">
					<value index="vDEF">Foo-Value</value>
				</field>
				<field index="settings.bar">
					<el index="el">
						<section index="1">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz1-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum1-Value</value>
									</field>
									<field index="dot.one">
										<value index="vDEF">dot.one-Value</value>
									</field>
									<field index="dot.two">
										<value index="vDEF">dot.two-Value</value>
									</field>
								</el>
							</itemType>
						</section>
						<section index="2">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz2-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum2-Value</value>
									</field>
								</el>
							</itemType>
						</section>
					</el>
				</field>
			</language>
		</sheet>
	</data>
</T3FlexForms>';

        $expected = [
            'sDEF' => [
                'settings' => [
                    'foo' => 'Foo-Value',
                    'bar' => [
                        1 => [
                            'baz' => 'Baz1-Value',
                            'bum' => 'Bum1-Value',
                            'dot' => [
                                'one' => 'dot.one-Value',
                                'two' => 'dot.two-Value',
                            ],
                        ],
                        2 => [
                            'baz' => 'Baz2-Value',
                            'bum' => 'Bum2-Value',
                        ],
                    ],
                ],
            ],
            'sheet2' => [
                'settings' => [
                    'foo' => 'Foo-Value',
                    'bar' => [
                        1 => [
                            'baz' => 'Baz1-Value',
                            'bum' => 'Bum1-Value',
                            'dot' => [
                                'one' => 'dot.one-Value',
                                'two' => 'dot.two-Value',
                            ],
                        ],
                        2 => [
                            'baz' => 'Baz2-Value',
                            'bum' => 'Bum2-Value',
                        ],
                    ],
                ],
            ],
        ];

        // The subject calls xml2array statically, which calls a runtime cache, this need to be mocked.
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->willReturn($cacheMock);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $flexFormService = new FlexFormService();
        $convertedFlexFormArray = $flexFormService->convertFlexFormContentToSheetsArray($input);
        self::assertSame($expected, $convertedFlexFormArray);
    }
}
