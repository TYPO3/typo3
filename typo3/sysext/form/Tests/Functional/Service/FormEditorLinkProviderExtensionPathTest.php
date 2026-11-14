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

namespace TYPO3\CMS\Form\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Service\FormEditorLinkProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormEditorLinkProviderExtensionPathTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_editor_link_tests',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://typo3-testing.local/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    #[Test]
    public function canEditReturnsTrueForExtensionFormIfSavingToExtensionPathsIsAllowed(): void
    {
        self::assertTrue(
            $this->get(FormEditorLinkProvider::class)
                ->canEdit('EXT:form_editor_link_tests/Resources/Private/Forms/EditableForm.form.yaml')
        );
    }

    #[Test]
    public function canEditReturnsFalseForExtensionFormOutsideOfAllowedExtensionPaths(): void
    {
        self::assertFalse(
            $this->get(FormEditorLinkProvider::class)
                ->canEdit('EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml')
        );
    }
}
