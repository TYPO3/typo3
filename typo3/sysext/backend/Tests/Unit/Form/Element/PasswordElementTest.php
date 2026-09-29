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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\PasswordElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class PasswordElementTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $languageServiceStub = self::createStub(LanguageService::class);
        $languageServiceStub->method('sL')->willReturnCallback(
            static fn(string $label): string => match ($label) {
                'LLL:EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf:copyToClipboard.title' => 'Copy %s to clipboard',
                default => $label,
            }
        );
        $GLOBALS['LANG'] = $languageServiceStub;
    }

    #[Test]
    public function renderOffersNoCopyToClipboardButtonByDefault(): void
    {
        $result = $this->renderWithConfig([]);

        self::assertStringNotContainsString('<typo3-copy-to-clipboard', $result['html']);
        self::assertNotContains('@typo3/backend/copy-to-clipboard.js', $this->getModuleNames($result));
    }

    #[Test]
    public function renderPlacesAHiddenCopyToClipboardButtonBesideTheInputWhenEnabled(): void
    {
        // A stored password is only shown obfuscated, so the button carries no text and stays
        // hidden until the script finds a plain value in the field. Until then the wrapper is
        // no input group, which would square the right corners of the input.
        $result = $this->renderWithConfig(['appearance' => ['copyToClipboard' => true]]);

        self::assertMatchesRegularExpression(
            '#<div>\s*<input type="password"[^>]*>\s*<typo3-copy-to-clipboard\s[^>]*hidden#',
            $result['html']
        );
        self::assertStringNotContainsString('input-group', $result['html']);
        self::assertMatchesRegularExpression('#<typo3-copy-to-clipboard[^>]+title="Copy Secret to clipboard"#', $result['html']);
        self::assertDoesNotMatchRegularExpression('#<typo3-copy-to-clipboard[^>]+text=#', $result['html']);
        self::assertContains('@typo3/backend/copy-to-clipboard.js', $this->getModuleNames($result));
    }

    #[Test]
    public function renderOffersNoCopyToClipboardButtonForAReadOnlyField(): void
    {
        $result = $this->renderWithConfig(['appearance' => ['copyToClipboard' => true], 'readOnly' => true]);

        self::assertStringNotContainsString('<typo3-copy-to-clipboard', $result['html']);
    }

    private function renderWithConfig(array $config): array
    {
        $nodeFactoryStub = self::createStub(NodeFactory::class);
        $fieldInformationStub = self::createStub(FieldInformation::class);
        $fieldInformationStub->method('render')->willReturn(['html' => '']);
        $nodeFactoryStub->method('create')->willReturn($fieldInformationStub);

        $subject = new PasswordElement(self::createStub(IconFactory::class));
        $subject->injectNodeFactory($nodeFactoryStub);
        $subject->setData([
            'tableName' => 'aTable',
            'fieldName' => 'secret',
            'databaseRow' => ['uid' => 1],
            'parameterArray' => [
                'itemFormElName' => 'data[aTable][1][secret]',
                'itemFormElValue' => '$argon2i$v=19$m=65536,t=16,p=1$c29tZXNhbHQ$hash',
                'fieldConf' => [
                    'label' => 'Secret',
                    'config' => ['type' => 'password'] + $config,
                ],
            ],
        ]);

        return $subject->render();
    }

    /**
     * @return list<string>
     */
    private function getModuleNames(array $result): array
    {
        return array_map(
            static fn(JavaScriptModuleInstruction $instruction): string => $instruction->getName(),
            $result['javaScriptModules']
        );
    }
}
