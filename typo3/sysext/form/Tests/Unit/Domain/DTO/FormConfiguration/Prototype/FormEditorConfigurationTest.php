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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration\Prototype;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEditorConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormEditorConfigurationTest extends UnitTestCase
{
    #[Test]
    public function fromArrayMapsKnownCoreKeys(): void
    {
        $configuration = FormEditorConfiguration::fromArray([
            'maximumUndoSteps' => '5',
            'stylesheets' => [200 => 'EXT:form/style.css'],
            'translationFiles' => [10 => 'EXT:form/lang.xlf'],
            'addInlineSettings' => ['FormEditor' => ['foo' => 'bar']],
            'formElementGroups' => ['input' => ['label' => 'Input']],
            'formEditorFluidConfiguration' => ['templatePathAndFilename' => 'x.html'],
            'formEditorPartials' => ['Inspector-TextEditor' => 'Inspector/TextEditor'],
        ]);

        self::assertSame(5, $configuration->maximumUndoSteps);
        self::assertSame(['EXT:form/style.css'], $configuration->stylesheets);
        self::assertSame([10 => 'EXT:form/lang.xlf'], $configuration->translationFiles);
        self::assertSame(['FormEditor' => ['foo' => 'bar']], $configuration->addInlineSettings);
        self::assertSame(['input' => ['label' => 'Input']], $configuration->formElementGroups);
        self::assertSame(['templatePathAndFilename' => 'x.html'], $configuration->formEditorFluidConfiguration);
        self::assertSame(['Inspector-TextEditor' => 'Inspector/TextEditor'], $configuration->formEditorPartials);
    }

    #[Test]
    public function fromArrayAppliesDefaultsForMissingKeys(): void
    {
        $configuration = FormEditorConfiguration::fromArray([]);

        self::assertSame(0, $configuration->maximumUndoSteps);
        self::assertSame([], $configuration->stylesheets);
        self::assertSame([], $configuration->translationFiles);
        self::assertSame([], $configuration->addInlineSettings);
        self::assertSame([], $configuration->dynamicJavaScriptModules);
        self::assertSame([], $configuration->formElementGroups);
        self::assertNull($configuration->formEditorFluidConfiguration);
        self::assertNull($configuration->formEditorPartials);
    }

    #[Test]
    public function rawConfigurationIsPreservedAsEscapeHatch(): void
    {
        $configuration = FormEditorConfiguration::fromArray([
            'maximumUndoSteps' => 10,
            'customThirdPartyKey' => ['nested' => 'value'],
        ]);

        self::assertSame('value', $configuration->get('customThirdPartyKey.nested'));
        self::assertTrue($configuration->has('customThirdPartyKey'));
    }

    #[Test]
    public function getAdditionalViewModelModulesReturnsConfiguredModules(): void
    {
        $configuration = FormEditorConfiguration::fromArray([
            'dynamicJavaScriptModules' => [
                'app' => '@vendor/app.js',
                'additionalViewModelModules' => [
                    100 => '@vendor/custom-view-model.js',
                ],
            ],
        ]);

        self::assertSame(['@vendor/custom-view-model.js'], $configuration->getAdditionalViewModelModules());
    }

    #[Test]
    public function getAdditionalViewModelModulesReturnsEmptyArrayWhenNotConfigured(): void
    {
        $configuration = FormEditorConfiguration::fromArray([
            'dynamicJavaScriptModules' => ['app' => '@vendor/app.js'],
        ]);

        self::assertSame([], $configuration->getAdditionalViewModelModules());
    }

    #[Test]
    public function getJavaScriptModulesForRolesReturnsOnlyRequestedStringModules(): void
    {
        $configuration = FormEditorConfiguration::fromArray([
            'dynamicJavaScriptModules' => [
                'app' => '@vendor/app.js',
                'mediator' => '@vendor/mediator.js',
                'viewModel' => '@vendor/view-model.js',
                'additionalViewModelModules' => [100 => '@vendor/custom.js'],
            ],
        ]);

        self::assertSame(
            [
                'app' => '@vendor/app.js',
                'viewModel' => '@vendor/view-model.js',
            ],
            $configuration->getJavaScriptModulesForRoles(['app', 'viewModel']),
        );
    }
}
