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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Renders an element, displaying MFA related information and providing
 * interactions like deactivation of active providers and MFA in general.
 *
 * @internal
 */
class MfaInfoElement extends AbstractFormElement
{
    private const ALLOWED_TABLES = ['be_users', 'fe_users'];

    protected MfaProviderRegistry $mfaProviderRegistry;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->mfaProviderRegistry = GeneralUtility::makeInstance(MfaProviderRegistry::class);
    }

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $currentBackendUser = $this->getBackendUser();
        $tableName = $this->data['tableName'];

        // This renderType only works for user tables: be_users, fe_users
        if (!in_array($tableName, self::ALLOWED_TABLES, true)) {
            return $resultArray;
        }

        // Initialize a user based on the current table name
        $targetUser = $tableName === 'be_users'
            ? GeneralUtility::makeInstance(BackendUserAuthentication::class)
            : GeneralUtility::makeInstance(FrontendUserAuthentication::class);

        $userId = (int)($this->data['databaseRow'][$targetUser->userid_column] ?? 0);
        $targetUser->enablecolumns = ['deleted' => true];
        $targetUser->setBeUserByUid($userId);

        $isDeactivationAllowed = true;
        // Providers from system maintainers can only be deactivated by system maintainers.
        // This check is however only be necessary if the target is a backend user.
        if ($targetUser instanceof BackendUserAuthentication) {
            $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
            $isTargetUserSystemMaintainer = $targetUser->isAdmin() && in_array($userId, $systemMaintainers, true);
            if ($isTargetUserSystemMaintainer && !$currentBackendUser->isSystemMaintainer()) {
                $isDeactivationAllowed = false;
            }
        }

        // Fetch providers from the mfa field
        $mfaProviders = json_decode($this->data['parameterArray']['itemFormElValue'] ?? '', true) ?? [];

        // Initialize variables
        $html = $childHtml = $activeProviders = $lockedProviders = [];
        $lang = $this->getLanguageService();
        $enabledLabel = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.mfa.enabled'));
        $disabledLabel = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.mfa.disabled'));
        $status = '<span class="label label-danger label-space-right t3js-mfa-status-label" data-alternative-label="' . $enabledLabel . '">' . $disabledLabel . '</span>';

        // Unset invalid providers
        foreach ($mfaProviders as $identifier => $providerSettings) {
            if (!$this->mfaProviderRegistry->hasProvider($identifier)) {
                unset($mfaProviders[$identifier]);
            }
        }

        if ($mfaProviders !== []) {
            // Check if remaining providers are active and/or locked for the user
            foreach ($mfaProviders as $identifier => $providerSettings) {
                $provider = $this->mfaProviderRegistry->getProvider($identifier);
                $propertyManager = MfaProviderPropertyManager::create($provider, $targetUser);
                if (!$provider->isActive($propertyManager)) {
                    continue;
                }
                $activeProviders[$identifier] = $provider;
                if ($provider->isLocked($propertyManager)) {
                    $lockedProviders[] = $identifier;
                }
            }

            if ($activeProviders !== []) {
                // Change status label to MFA being enabled
                $status = '<span class="label label-success label-space-right t3js-mfa-status-label"' . ' data-alternative-label="' . $disabledLabel . '">' . $enabledLabel . '</span>';

                // Add providers list
                $childHtml[] = '<ul class="list-group t3js-mfa-active-providers-list">';
                foreach ($activeProviders as $identifier => $activeProvider) {
                    $childHtml[] = '<li class="list-group-item" id="provider-' . htmlspecialchars((string)$identifier) . '" style="line-height: 2.1em;">';
                    $childHtml[] =  $this->iconFactory->getIcon($activeProvider->getIconIdentifier(), Icon::SIZE_SMALL);
                    $childHtml[] =  htmlspecialchars($lang->sL($activeProvider->getTitle()));
                    if (in_array($identifier, $lockedProviders, true)) {
                        $childHtml[] = '<span class="label label-danger">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.locked')) . '</span>';
                    } else {
                        $childHtml[] = '<span class="label label-success">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.active')) . '</span>';
                    }
                    if ($isDeactivationAllowed) {
                        $childHtml[] = '<button type="button"';
                        $childHtml[] =  ' class="btn btn-default btn-sm pull-right t3js-deactivate-provider-button"';
                        $childHtml[] =  ' data-confirmation-title="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider'), $lang->sL($activeProvider->getTitle()))) . '"';
                        $childHtml[] =  ' data-confirmation-content="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider.confirmation.text'), $lang->sL($activeProvider->getTitle()))) . '"';
                        $childHtml[] =  ' data-confirmation-cancel-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel')) . '"';
                        $childHtml[] =  ' data-confirmation-deactivate-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deactivate')) . '"';
                        $childHtml[] =  ' data-provider="' . htmlspecialchars((string)$identifier) . '"';
                        $childHtml[] =  ' title="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider'), $lang->sL($activeProvider->getTitle()))) . '"';
                        $childHtml[] =  '>';
                        $childHtml[] =      $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)->render('inline');
                        $childHtml[] = '</button>';
                    }
                    $childHtml[] = '</li>';
                }
                $childHtml[] = '</ul>';
            }
        }

        $fieldId = 't3js-form-field-mfa-id' . StringUtility::getUniqueId('-');

        $html[] = '<div class="formengine-field-item t3js-formengine-field-item" id="' . htmlspecialchars($fieldId) . '">';
        $html[] =   '<div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth($this->defaultInputWidth) . 'px">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               implode(PHP_EOL, $childHtml);
        if ($isDeactivationAllowed) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] =   '<div class="help-block">';
            $html[] =       '<button type="button"';
            $html[] =           ' class="t3js-deactivate-mfa-button btn btn-danger ' . ($activeProviders === [] ? 'disabled" disabled="disabled' : '') . '"';
            $html[] =           ' data-confirmation-title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa')) . '"';
            $html[] =           ' data-confirmation-content="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa.confirmation.text')) . '"';
            $html[] =           ' data-confirmation-cancel-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel')) . '"';
            $html[] =           ' data-confirmation-deactivate-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deactivate')) . '"';
            $html[] =       '>';
            $html[] =           $this->iconFactory->getIcon('actions-toggle-off', Icon::SIZE_SMALL)->render('inline');
            $html[] =           htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa'));
            $html[] =       '</button>';
            $html[] =   '</div>';
            $html[] = '</div>';
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        // JavaScript is not needed in case deactivation is not allowed or no active providers exist
        if ($isDeactivationAllowed && $activeProviders !== []) {
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/Backend/FormEngine/Element/MfaInfoElement'
            )->instance('#' . $fieldId, ['userId' => $userId, 'tableName' => $tableName]);
        }

        $resultArray['html'] = $status . implode(PHP_EOL, $html);
        return $resultArray;
    }
}
