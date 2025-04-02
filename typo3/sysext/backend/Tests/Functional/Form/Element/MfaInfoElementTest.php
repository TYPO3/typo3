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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\MfaInfoElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MfaInfoElementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');

        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->enablecolumns = ['deleted' => true];
        $GLOBALS['BE_USER']->setBeUserByUid(1);

        // Default LANG mock just returns incoming value as label if calling ->sL()
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with(self::anything())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;
    }

    #[Test]
    public function renderReturnsEmptyResultOnInvalidTableTest(): void
    {
        $result = $this->getFormElementResult([
            'tableName' => 'some_table',
        ]);

        self::assertEmpty($result['html']);
    }

    #[Test]
    public function renderReturnsElementWithMfaDisabledTest(): void
    {
        $result = $this->getFormElementResult([
            'tableName' => 'be_users',
            'databaseRow' => [
                'uid' => 3,
            ],
            'parameterArray' => [
                'itemFormElValue' => '[]',
                'fieldConf' => [
                    'label' => 'foo',
                ],
            ],
        ]);

        // MFA is disabled
        self::assertMatchesRegularExpression('/<span.*class="badge badge-danger.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.disabled/s', $result['html']);
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-success.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.enabled/s', $result['html']);
        // MFA can NOT be deactivated
        self::assertMatchesRegularExpression('/<button.*class="t3js-deactivate-mfa-button btn btn-danger mt-2 disabled".*disabled="disabled"/s', $result['html']);
        // JavaScript is NOT added
        self::assertEmpty($result['javaScriptModules']);
    }

    #[Test]
    public function renderReturnsElementWithoutInvalidProviderTest(): void
    {
        $result = $this->getFormElementResult([
            'tableName' => 'be_users',
            'databaseRow' => [
                'uid' => 4,
            ],
            'parameterArray' => [
                'itemFormElValue' => '{"invalid":{"active":true}}',
                'fieldConf' => [
                    'label' => 'foo',
                ],
            ],
        ]);

        // MFA is disabled
        self::assertMatchesRegularExpression('/<span.*class="badge badge-danger.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.disabled/s', $result['html']);
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-success.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.enabled/s', $result['html']);
        // MFA can NOT be deactivated
        self::assertMatchesRegularExpression('/<button.*class="t3js-deactivate-mfa-button btn btn-danger mt-2 disabled".*disabled="disabled"/s', $result['html']);
        // JavaScript is NOT added
        self::assertEmpty($result['javaScriptModules']);
    }

    #[Test]
    public function renderReturnsElementWithMfaActiveTest(): void
    {
        $result = $this->getFormElementResult([
            'tableName' => 'be_users',
            'databaseRow' => [
                'uid' => 4,
            ],
            'parameterArray' => [
                'itemFormElValue' => '{"totp":{"secret":"KRMVATZTJFZUC53FONXW2ZJB","active":true,"attempts":2}}',
                'fieldConf' => [
                    'label' => 'foo',
                ],
            ],
        ]);

        // Mfa is enabled
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-danger.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.disabled/s', $result['html']);
        self::assertMatchesRegularExpression('/<span.*class="badge badge-success.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.enabled/s', $result['html']);
        // Totp item exist
        self::assertMatchesRegularExpression('/<li.*class="list-group-item".*id="provider-totp"/s', $result['html']);
        // Recovery codes item does NOT exist
        self::assertStringNotContainsString('id="provider-recovery-codes"', $result['html']);
        // No item is locked
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-danger".*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.locked/s', $result['html']);
        // Item can be deactivated
        self::assertMatchesRegularExpression('/<button.*class="btn btn-default btn-sm float-end t3js-deactivate-provider-button"/s', $result['html']);
        // MFA can be deactivated
        self::assertMatchesRegularExpression('/<button.*class="t3js-deactivate-mfa-button btn btn-danger mt-2 "/s', $result['html']);
        // JavaScript is added
        self::assertInstanceOf(JavaScriptModuleInstruction::class, $result['javaScriptModules'][0]);
        self::assertSame('@typo3/backend/form-engine/element/mfa-info-element.js', $result['javaScriptModules'][0]->getName());
    }

    #[Test]
    public function renderReturnsElementWithMfaActiveAndLockedProvidersTest(): void
    {
        $result = $this->getFormElementResult([
            'tableName' => 'be_users',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'itemFormElValue' => '{"totp":{"secret":"KRMVATZTJFZUC53FONXW2ZJB","active":true,"attempts":2},"recovery-codes":{"active":true,"attempts":3,"codes":[]}}',
                'fieldConf' => [
                    'label' => 'foo',
                ],
            ],
        ]);

        // Mfa is enabled
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-danger.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.disabled/s', $result['html']);
        self::assertMatchesRegularExpression('/<span.*class="badge badge-success.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.enabled/s', $result['html']);
        // Totp item exists
        self::assertMatchesRegularExpression('/<li.*class="list-group-item".*id="provider-totp"/s', $result['html']);
        // Recovery codes item exists
        self::assertMatchesRegularExpression('/<li.*class="list-group-item".*id="provider-recovery-codes"/s', $result['html']);
        // Item is locked
        self::assertMatchesRegularExpression('/<span.*class="badge badge-danger".*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.locked/s', $result['html']);
        // Items can be deactivated
        self::assertMatchesRegularExpression('/<button.*class="btn btn-default btn-sm float-end t3js-deactivate-provider-button"/s', $result['html']);
        // MFA can be deactivated
        self::assertMatchesRegularExpression('/<button.*class="t3js-deactivate-mfa-button btn btn-danger mt-2 "/s', $result['html']);
        // JavaScript is added
        self::assertInstanceOf(JavaScriptModuleInstruction::class, $result['javaScriptModules'][0]);
        self::assertSame('@typo3/backend/form-engine/element/mfa-info-element.js', $result['javaScriptModules'][0]->getName());
    }

    #[Test]
    public function renderReturnsElementWithoutDeactivationButtonsOnMissingPermissionsTest(): void
    {
        // Make the target user a system maintainer. Since the current user (1)
        // is only admin, he is not allowed to deactivate the providers, nor MFA.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] = ['5'];

        $result = $this->getFormElementResult([
            'tableName' => 'be_users',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'itemFormElValue' => '{"totp":{"secret":"KRMVATZTJFZUC53FONXW2ZJB","active":true,"attempts":2},"recovery-codes":{"active":true,"attempts":3,"codes":[]}}',
                'fieldConf' => [
                    'label' => 'foo',
                ],
            ],
        ]);

        // Mfa is enabled
        self::assertDoesNotMatchRegularExpression('/<span.*class="badge badge-danger.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.disabled/s', $result['html']);
        self::assertMatchesRegularExpression('/<span.*class="badge badge-success.*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.mfa.enabled/s', $result['html']);
        // Totp item exists
        self::assertMatchesRegularExpression('/<li.*class="list-group-item".*id="provider-totp"/s', $result['html']);
        // Recovery codes item exists
        self::assertMatchesRegularExpression('/<li.*class="list-group-item".*id="provider-recovery-codes"/s', $result['html']);
        // Item (recovery codes) is locked
        self::assertMatchesRegularExpression('/<span.*class="badge badge-danger".*>LLL:EXT:core\/Resources\/Private\/Language\/locallang_core.xlf:labels.locked/s', $result['html']);
        // Items deactivation button is not shown
        self::assertStringNotContainsString('t3js-deactivate-provider-button', $result['html']);
        // MFA deactivation button is not shown
        self::assertStringNotContainsString('t3js-deactivate-mfa-button', $result['html']);
        // JavaScript is NOT added
        self::assertEmpty($result['javaScriptModules']);
    }

    protected function getFormElementResult(array $data): array
    {
        $node = new MfaInfoElement($this->get(IconFactory::class), $this->get(MfaProviderRegistry::class));
        $node->setData($data);
        return $node->render();
    }
}
