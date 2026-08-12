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

namespace TYPO3\CMS\Backend\Tests\Unit\CodeEditor;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\CodeEditor\CodeEditorConfiguration;
use TYPO3\CMS\Backend\CodeEditor\Exception\InvalidModeException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CodeEditorConfigurationTest extends UnitTestCase
{
    #[Test]
    public function latestDefaultModeIsReturned(): void
    {
        $subject = $this->createSubject([
            'modes' => [
                'firstDefault' => ['module' => $this->createModule(), 'default' => true],
                'html' => ['module' => $this->createModule(), 'default' => true],
                'css' => ['module' => $this->createModule()],
            ],
        ]);

        self::assertSame('html', $subject->getDefaultMode()->formatCode);
    }

    #[Test]
    public function missingDefaultModeThrowsException(): void
    {
        $subject = $this->createSubject([
            'modes' => [
                'css' => ['module' => $this->createModule()],
            ],
        ]);

        $this->expectException(InvalidModeException::class);
        $this->expectExceptionCode(1786492801);

        $subject->getDefaultMode();
    }

    #[Test]
    public function modeIsFetchedByFormatCode(): void
    {
        $subject = $this->createSubject([
            'modes' => [
                'css' => ['module' => $this->createModule()],
                'code' => ['module' => $this->createModule()],
            ],
        ]);

        self::assertTrue($subject->hasMode('code'));
        self::assertSame('code', $subject->getModeByFormatCode('code')->formatCode);
    }

    #[Test]
    public function unknownFormatCodeThrowsException(): void
    {
        $subject = $this->createSubject(['modes' => []]);

        self::assertFalse($subject->hasMode('code'));

        $this->expectException(InvalidModeException::class);
        $this->expectExceptionCode(1499710203);

        $subject->getModeByFormatCode('code');
    }

    #[Test]
    public function modeIsFetchedByFileExtension(): void
    {
        $subject = $this->createSubject([
            'modes' => [
                'css' => ['module' => $this->createModule(), 'extensions' => ['css']],
                'code' => ['module' => $this->createModule(), 'extensions' => ['ext', 'fext']],
            ],
        ]);

        self::assertSame('code', $subject->getModeByFileExtension('fext')->formatCode);
    }

    #[Test]
    public function unknownFileExtensionThrowsException(): void
    {
        $subject = $this->createSubject([
            'modes' => [
                'css' => ['module' => $this->createModule(), 'extensions' => ['css']],
            ],
        ]);

        $this->expectException(InvalidModeException::class);
        $this->expectExceptionCode(1500306488);

        $subject->getModeByFileExtension('fext');
    }

    #[Test]
    public function addonsAreReturnedInDefinedOrder(): void
    {
        $subject = $this->createSubject(['addons' => $this->createAddonConfiguration()]);

        $identifiers = [];
        foreach ($subject->getAddons() as $addon) {
            $identifiers[] = $addon->identifier;
        }

        self::assertSame(
            ['addon/global', 'addon/another/global', 'addon/with/same/cssfile', 'addon/with/settings'],
            $identifiers
        );
    }

    #[Test]
    public function addonSettingsAreProperlyCompiled(): void
    {
        $subject = $this->createSubject(['addons' => $this->createAddonConfiguration()]);

        $expected = [
            'foobar' => false,
            'husel' => 'pusel',
            'randomInt' => 4,
        ];

        self::assertSame($expected, $subject->getAddonSettings());
    }

    #[Test]
    public function cssFilesAreAssignedToAddons(): void
    {
        $subject = $this->createSubject(['addons' => $this->createAddonConfiguration()]);

        self::assertSame(
            ['EXT:foobar/Resources/Public/Css/Addon.css'],
            $subject->getAddons()[1]->cssFiles
        );
    }

    #[Test]
    public function configurationIsCompiledAndCachedIfCacheIsEmpty(): void
    {
        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('getActivePackages')->willReturn([]);

        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheMock->method('get')->willReturn(false);
        $cacheMock->expects($this->once())->method('set')->with(
            'aCacheIdentifier',
            ['modes' => [], 'addons' => []]
        );

        $subject = new CodeEditorConfiguration($packageManagerStub, $cacheMock, 'aCacheIdentifier');

        self::assertSame([], $subject->getModes());
        self::assertSame([], $subject->getAddons());
    }

    private function createSubject(array $configuration): CodeEditorConfiguration
    {
        $configuration += ['modes' => [], 'addons' => []];

        $cacheStub = self::createStub(FrontendInterface::class);
        $cacheStub->method('get')->willReturn($configuration);

        return new CodeEditorConfiguration(
            self::createStub(PackageManager::class),
            $cacheStub,
            'aCacheIdentifier'
        );
    }

    private function createModule(): JavaScriptModuleInstruction
    {
        return JavaScriptModuleInstruction::create('@test/mode', 'mode')->invoke();
    }

    private function createAddonConfiguration(): array
    {
        return [
            'addon/global' => [],
            'addon/another/global' => [
                'cssFiles' => ['EXT:foobar/Resources/Public/Css/Addon.css'],
            ],
            'addon/with/same/cssfile' => [
                'cssFiles' => ['EXT:foobar/Resources/Public/Css/Addon.css'],
                'options' => [
                    'foobar' => true,
                    'husel' => 'pusel',
                ],
            ],
            'addon/with/settings' => [
                'options' => [
                    'foobar' => false,
                    'randomInt' => 4, // chosen by fair dice roll
                ],
            ],
        ];
    }
}
