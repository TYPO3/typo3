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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
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
    private const ALLOWED_TABLES = ['be_users', 'fe_users', 'be_users_settings'];

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly MfaProviderRegistry $mfaProviderRegistry,
        private readonly UriBuilder $uriBuilder,
    ) {}

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
        $targetUser = ($tableName === 'be_users' || $tableName === 'be_users_settings')
            ? GeneralUtility::makeInstance(BackendUserAuthentication::class)
            : GeneralUtility::makeInstance(FrontendUserAuthentication::class);

        $userId = (int)($this->data['databaseRow'][$targetUser->userid_column] ?? 0);
        $targetUser->enablecolumns = ['deleted' => true];
        $targetUser->setBeUserByUid($userId);

        $isDeactivationAllowed = true;
        // Providers from system maintainers can only be deactivated by system maintainers.
        // However, this check is only necessary if the target is a backend user.
        if (($targetUser instanceof BackendUserAuthentication)
            && $targetUser->isSystemMaintainer(true)
            && !$currentBackendUser->isSystemMaintainer()
        ) {
            $isDeactivationAllowed = false;
        }

        // Fetch providers from the mfa field
        $mfaProviders = json_decode($this->data['parameterArray']['itemFormElValue'] ?? '', true) ?? [];
        $hasFormValue = $mfaProviders !== [];

        // Initialize variables
        $html = $childHtml = $activeProviders = $lockedProviders = $mfaSetupInfo = [];
        $lang = $this->getLanguageService();
        $enabledLabel = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.mfa.enabled'));
        $disabledLabel = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.mfa.disabled'));
        $status = '<span class="badge badge-danger badge-space-end t3js-mfa-status-label" data-alternative-label="' . $enabledLabel . '">' . $disabledLabel . '</span>';

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
                $status = '<span class="badge badge-success badge-space-end t3js-mfa-status-label mb-2"' . ' data-alternative-label="' . $disabledLabel . '">' . $enabledLabel . '</span>';
                $childHtml = $this->buildProviderListHtml($activeProviders, $lockedProviders, $isDeactivationAllowed);
            }
        } elseif (!$hasFormValue) {
            // Fallback: read MFA state directly from the user when no form value was provided
            $activeProviders = $this->mfaProviderRegistry->getActiveProviders($targetUser);
            $lockedProviders = array_keys($this->mfaProviderRegistry->getLockedProviders($targetUser));

            if ($activeProviders !== []) {
                $status = '<span class="badge badge-success badge-space-end t3js-mfa-status-label mb-2"' . ' data-alternative-label="' . $disabledLabel . '">' . $enabledLabel . '</span>';
                $childHtml = $this->buildProviderListHtml($activeProviders, $lockedProviders, false);
            }

            $mfaSetupInfo[] = '<div class="form-description">' . nl2br(htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:mfa_providers.description'))) . '</div>';
            if (!$this->mfaProviderRegistry->hasProviders()) {
                $mfaSetupInfo[] = '<span class="badge badge-danger">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:mfa_providers.not_available')) . '</span>';
            } else {
                $mfaSetupInfo[] = '<div class="form-group"><div class="form-control-wrap t3js-file-controls">';
                $mfaSetupInfo[] = '<a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('mfa')) . '" class="btn btn-default">';
                $mfaSetupInfo[] =  htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:mfa_providers.' . ($activeProviders !== [] ? 'manage_link_title' : 'setup_link_title')));
                $mfaSetupInfo[] = '</a>';
                $mfaSetupInfo[] = '</div></div>';
            }
        }

        $fieldId = 't3js-form-field-mfa-id' . StringUtility::getUniqueId('-');

        $html[] = '<div class="formengine-field-item t3js-formengine-field-item" id="' . htmlspecialchars($fieldId) . '">';
        $html[] =     implode(PHP_EOL, $mfaSetupInfo);
        $html[] =   '<div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth($this->defaultInputWidth) . 'px">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-item-element">';
        $html[] =               implode(PHP_EOL, $childHtml);
        if ($isDeactivationAllowed) {
            $html[] = '<div class="form-wizards-item-bottom">';
            $html[] =     '<button type="button"';
            $html[] =         ' class="t3js-deactivate-mfa-button btn btn-danger mt-2 ' . ($activeProviders === [] ? 'disabled" disabled="disabled' : '') . '"';
            $html[] =         ' data-confirmation-title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa')) . '"';
            $html[] =         ' data-confirmation-content="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa.confirmation.text')) . '"';
            $html[] =         ' data-confirmation-cancel-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel')) . '"';
            $html[] =         ' data-confirmation-deactivate-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deactivate')) . '"';
            $html[] =     '>';
            $html[] =     $this->iconFactory->getIcon('actions-toggle-off', IconSize::SMALL)->render('inline');
            $html[] =     htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfa'));
            $html[] =     '</button>';
            $html[] = '</div>';
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        // JavaScript is not needed in case deactivation is not allowed or no active providers exist
        if ($isDeactivationAllowed && $activeProviders !== []) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
                '@typo3/backend/form-engine/element/mfa-info-element.js'
            )->instance('#' . $fieldId, ['userId' => $userId, 'tableName' => $tableName]);
        }

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend($status . implode(PHP_EOL, $html));
        return $resultArray;
    }

    /**
     * Build the HTML list of active MFA providers with their status badges
     * and optional deactivation buttons.
     *
     * @param MfaProviderManifestInterface[] $activeProviders
     * @param string[] $lockedProviders
     * @return string[]
     */
    private function buildProviderListHtml(
        array $activeProviders,
        array $lockedProviders,
        bool $isDeactivationAllowed,
    ): array {
        $childHtml = [];
        $lang = $this->getLanguageService();

        $childHtml[] = '<ul class="list-group t3js-mfa-active-providers-list">';
        foreach ($activeProviders as $identifier => $activeProvider) {
            $childHtml[] = '<li class="list-group-item" id="provider-' . htmlspecialchars((string)$identifier) . '">';
            $childHtml[] =  $this->iconFactory->getIcon($activeProvider->getIconIdentifier(), IconSize::SMALL);
            $childHtml[] =  htmlspecialchars($lang->sL($activeProvider->getTitle()));
            if (in_array($identifier, $lockedProviders, true)) {
                $childHtml[] = '<span class="badge badge-danger">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.locked')) . '</span>';
            } else {
                $childHtml[] = '<span class="badge badge-success">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.active')) . '</span>';
            }
            if ($isDeactivationAllowed) {
                $childHtml[] = '<button type="button"';
                $childHtml[] =  ' class="btn btn-default btn-sm float-end t3js-deactivate-provider-button"';
                $childHtml[] =  ' data-confirmation-title="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider'), $lang->sL($activeProvider->getTitle()))) . '"';
                $childHtml[] =  ' data-confirmation-content="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider.confirmation.text'), $lang->sL($activeProvider->getTitle()))) . '"';
                $childHtml[] =  ' data-confirmation-cancel-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel')) . '"';
                $childHtml[] =  ' data-confirmation-deactivate-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deactivate')) . '"';
                $childHtml[] =  ' data-provider="' . htmlspecialchars((string)$identifier) . '"';
                $childHtml[] =  ' title="' . htmlspecialchars(sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.deactivateMfaProvider'), $lang->sL($activeProvider->getTitle()))) . '"';
                $childHtml[] =  '>';
                $childHtml[] =      $this->iconFactory->getIcon('actions-delete', IconSize::SMALL)->render('inline');
                $childHtml[] = '</button>';
            }
            $childHtml[] = '</li>';
        }
        $childHtml[] = '</ul>';

        return $childHtml;
    }
}
