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

namespace TYPO3\CMS\Setup\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\DefaultAvatarProvider;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\OfficialLanguages;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\Setting as SystemLogSettingAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent;

/**
 * Script class for the Setup module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class SetupModuleController
{
    protected const PASSWORD_NOT_UPDATED = 0;
    protected const PASSWORD_UPDATED = 1;
    protected const PASSWORD_NOT_THE_SAME = 2;
    protected const PASSWORD_OLD_WRONG = 3;
    protected const PASSWORD_POLICY_FAILED = 4;

    protected array $overrideConf = [];
    protected bool $languageUpdate = false;
    protected bool $pagetreeNeedsRefresh = false;
    protected array $tsFieldConf = [];
    protected int $passwordIsUpdated = self::PASSWORD_NOT_UPDATED;
    protected bool $passwordIsSubmitted = false;
    protected bool $setupIsUpdated = false;
    protected bool $settingsAreResetToDefault = false;

    protected PasswordPolicyValidator $passwordPolicyValidator;

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly MfaProviderRegistry $mfaProviderRegistry,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly UriBuilder $uriBuilder,
        protected readonly FormProtectionFactory $formProtectionFactory,
        protected readonly Locales $locales,
    ) {
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? 'default';

        $action = PasswordPolicyAction::UPDATE_USER_PASSWORD;
        if ($this->getBackendUser()->getOriginalUserIdWhenInSwitchUserMode()) {
            $action = PasswordPolicyAction::UPDATE_USER_PASSWORD_SWITCH_USER_MODE;
        }

        $this->passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            $action,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
    }

    /**
     * Injects the request object, checks if data should be saved, and prepares a HTML page
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initialize($request);
        $this->storeIncomingData($request);
        if ($this->pagetreeNeedsRefresh) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $this->addFlashMessages($view);
        $this->getButtons($view);
        $view->assignMultiple([
            'isLanguageUpdate' => $this->languageUpdate,
            'menuItems' => $this->renderUserSetup(),
            'menuId' => 'DTM-375167ed176e8c9caf4809cee7df156c',
            'formToken' => $formProtection->generateToken('BE user setup', 'edit'),
        ]);
        return $view->renderResponse('Main');
    }

    /**
     * Initializes the module for display of the settings form.
     */
    protected function initialize(ServerRequestInterface $request): ModuleTemplate
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        $view = $this->moduleTemplateFactory->create($request);
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/form-engine.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/setup/setup-module.js');
        $this->processAdditionalJavaScriptModules();
        $this->pageRenderer->addInlineSetting('FormEngine', 'formName', 'editform');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'FormEngine.remainingCharacters' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remainingCharacters'),
        ]);
        $view->setTitle($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:UserSettings'));
        // Getting the 'override' values as set might be set in user TSconfig
        $this->overrideConf = $backendUser->getTSConfig()['setup.']['override.'] ?? [];
        // Getting the disabled fields might be set in user TSconfig (eg setup.fields.password.disabled=1)
        $this->tsFieldConf = $backendUser->getTSConfig()['setup.']['fields.'] ?? [];
        // if password is disabled, disable repeat of password too (password2)
        if ($this->tsFieldConf['password.']['disabled'] ?? false) {
            $this->tsFieldConf['password2.']['disabled'] = 1;
            $this->tsFieldConf['passwordCurrent.']['disabled'] = 1;
        }
        return $view;
    }

    protected function processAdditionalJavaScriptModules(): void
    {
        $event = new AddJavaScriptModulesEvent();
        $event = $this->eventDispatcher->dispatch($event);
        foreach ($event->getJavaScriptModules() as $specifier) {
            $this->pageRenderer->loadJavaScriptModule($specifier);
        }
        foreach ($event->getModules() as $moduleName) {
            // The deprecation is added in AddJavaScriptModulesEvent::addModule, and therefore silenced here.
            $this->pageRenderer->loadRequireJsModule($moduleName, null, true);
        }
    }

    /**
     * If settings are submitted via POST, store them
     */
    protected function storeIncomingData(ServerRequestInterface $request): void
    {
        $postData = $request->getParsedBody();
        if (!is_array($postData) || empty($postData)) {
            return;
        }

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        // First check if something is submitted in the data-array from POST vars
        $d = $postData['data'] ?? null;
        $columns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'];
        $backendUser = $this->getBackendUser();
        $beUserId = (int)$backendUser->user['uid'];
        $storeRec = [];
        $doSaveData = false;
        $fieldList = $this->getFieldsFromShowItem();
        if (is_array($d) && $formProtection->validateToken((string)($postData['formToken'] ?? ''), 'BE user setup', 'edit')) {
            // UC hashed before applying changes
            $save_before = md5(serialize($backendUser->uc));
            // PUT SETTINGS into the ->uc array:
            // Reload left frame when switching BE language
            if (isset($d['be_users']['lang']) && $d['be_users']['lang'] !== $backendUser->user['lang']) {
                $this->languageUpdate = true;
            }
            // Reload pagetree if the title length is changed
            if (isset($d['titleLen']) && $d['titleLen'] !== $backendUser->uc['titleLen']) {
                $this->pagetreeNeedsRefresh = true;
            }
            if ($d['setValuesToDefault']) {
                // If every value should be default
                $backendUser->resetUC();
                $this->settingsAreResetToDefault = true;
            } elseif ($d['save']) {
                // Save all submitted values if they are no array (arrays are with table=be_users) and exists in $GLOBALS['TYPO3_USER_SETTINGS'][columns]
                foreach ($columns as $field => $config) {
                    if (!in_array($field, $fieldList, true)) {
                        continue;
                    }
                    if (($config['table']  ?? '') === 'be_users' && !in_array($field, ['password', 'password2', 'passwordCurrent', 'email', 'realName', 'admin', 'avatar'], true)) {
                        if (!isset($config['access']) || $this->checkAccess($config) && ($backendUser->user[$field] !== $d['be_users'][$field])) {
                            if (($config['type'] ?? false) === 'check') {
                                $fieldValue = isset($d['be_users'][$field]) ? 1 : 0;
                            } else {
                                $fieldValue = $d['be_users'][$field];
                            }
                            $storeRec['be_users'][$beUserId][$field] = $fieldValue;
                            $backendUser->user[$field] = $fieldValue;
                        }
                    }
                    if (($config['type'] ?? false) === 'check') {
                        $backendUser->uc[$field] = isset($d[$field]) ? 1 : 0;
                    } else {
                        $backendUser->uc[$field] = htmlspecialchars($d[$field] ?? '');
                    }
                }
                // Personal data for the users be_user-record (email, name, password...)
                // If email and name is changed, set it in the users record:
                $be_user_data = $d['be_users'];
                // Possibility to modify the transmitted values. Useful to do transformations, like RSA password decryption
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'] ?? [] as $function) {
                    $params = ['be_user_data' => &$be_user_data];
                    GeneralUtility::callUserFunction($function, $params, $this);
                }
                $this->passwordIsSubmitted = (string)$be_user_data['password'] !== '';
                $passwordIsConfirmed = $this->passwordIsSubmitted && $be_user_data['password'] === $be_user_data['password2'];

                // Validate password against password policy
                $contextData = new ContextData(
                    loginMode: 'BE',
                    currentPasswordHash: $this->getBackendUser()->user['password'],
                    newUserFullName: $be_user_data['realName']
                );
                $contextData->setData('currentUsername', $this->getBackendUser()->user['username']);
                $event = $this->eventDispatcher->dispatch(
                    new EnrichPasswordValidationContextDataEvent(
                        $contextData,
                        $be_user_data,
                        self::class
                    )
                );
                $contextData = $event->getContextData();

                $passwordValid = true;
                if ($passwordIsConfirmed &&
                    !$this->passwordPolicyValidator->isValidPassword($be_user_data['password'], $contextData)
                ) {
                    $passwordValid = false;
                    $this->passwordIsUpdated = self::PASSWORD_POLICY_FAILED;
                }

                // Update the real name:
                if (isset($be_user_data['realName']) && $be_user_data['realName'] !== $backendUser->user['realName']) {
                    $backendUser->user['realName'] = ($storeRec['be_users'][$beUserId]['realName'] = substr($be_user_data['realName'], 0, 80));
                }
                // Update the email address:
                if (isset($be_user_data['email']) && $be_user_data['email'] !== $backendUser->user['email']) {
                    $backendUser->user['email'] = ($storeRec['be_users'][$beUserId]['email'] = substr($be_user_data['email'], 0, 255));
                }
                // Update the password:
                if ($passwordIsConfirmed && $passwordValid) {
                    if ($backendUser->isAdmin()) {
                        $passwordOk = true;
                    } else {
                        $currentPasswordHashed = $backendUser->user['password'];
                        $passwordOk = false;
                        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
                        try {
                            $hashInstance = $saltFactory->get($currentPasswordHashed, 'BE');
                            $passwordOk = $hashInstance->checkPassword($be_user_data['passwordCurrent'], $currentPasswordHashed);
                        } catch (InvalidPasswordHashException $e) {
                            // Could not find hash class responsible for existing password. This is a
                            // misconfiguration and user can not change its password.
                        }
                    }
                    if ($passwordOk) {
                        $this->passwordIsUpdated = self::PASSWORD_UPDATED;
                        $storeRec['be_users'][$beUserId]['password'] = $be_user_data['password'];
                    } else {
                        $this->passwordIsUpdated = self::PASSWORD_OLD_WRONG;
                    }
                } elseif ($passwordIsConfirmed) {
                    $this->passwordIsUpdated = self::PASSWORD_POLICY_FAILED;
                } else {
                    $this->passwordIsUpdated = self::PASSWORD_NOT_THE_SAME;
                }

                $this->setAvatarFileUid($beUserId, $be_user_data['avatar'], $storeRec);

                $doSaveData = true;
            }
            // Inserts the overriding values.
            $backendUser->overrideUC();
            $save_after = md5(serialize($backendUser->uc));
            // If something in the uc-array of the user has changed, we save the array...
            if ($save_before != $save_after) {
                $backendUser->writeUC();
                $backendUser->writelog(SystemLogType::SETTING, SystemLogSettingAction::CHANGE, SystemLogErrorClassification::MESSAGE, 1, 'Personal settings changed', []);
                $this->setupIsUpdated = true;
            }
            // Persist data if something has changed:
            if (!empty($storeRec) && $doSaveData) {
                // Set user to admin to circumvent DataHandler restrictions.
                // Not using isAdmin() to fetch the original value, just in case it has been boolean casted.
                $savedUserAdminState = $backendUser->user['admin'];
                $backendUser->user['admin'] = true;
                // Make dedicated instance of TCE for storing the changes.
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($storeRec, [], $backendUser);
                // This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
                $dataHandler->bypassWorkspaceRestrictions = true;
                $dataHandler->process_datamap();
                // reset the user record admin flag to previous value, just in case it gets used any further.
                $backendUser->user['admin'] = $savedUserAdminState;
                if ($this->passwordIsUpdated === self::PASSWORD_NOT_UPDATED || count($storeRec['be_users'][$beUserId]) > 1) {
                    $this->setupIsUpdated = true;
                }
                BackendUtility::setUpdateSignal('updateTopbar');
            }
        }
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(ModuleTemplate $view): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        $saveButton = $buttonBar->makeInputButton()
            ->setName('data[save]')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setValue('1')
            ->setForm('SetupModuleController')
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));

        $buttonBar->addButton($saveButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('user_setup')
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'));
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * renders the data for all tabs in the user setup and returns
     * everything that is needed with tabs and dyntab menu
     *
     * @return array Ready to use for the dyntabmenu itemarray
     */
    protected function renderUserSetup(): array
    {
        $backendUser = $this->getBackendUser();
        $html = '';
        $result = [];
        $firstTabLabel = '';
        $code = [];
        $fieldArray = $this->getFieldsFromShowItem();
        $tabLabel = '';
        foreach ($fieldArray as $fieldName) {
            if (str_starts_with($fieldName, '--div--;')) {
                if ($firstTabLabel === '') {
                    // First tab
                    $tabLabel = $this->getLabel(substr($fieldName, 8), '', false);
                    $firstTabLabel = $tabLabel;
                } else {
                    $result[] = [
                        'label' => $tabLabel,
                        'content' => count($code) ? implode(LF, $code) : '',
                    ];
                    $tabLabel = $this->getLabel(substr($fieldName, 8), '', false);
                    $code = [];
                }
                continue;
            }

            $config = $GLOBALS['TYPO3_USER_SETTINGS']['columns'][$fieldName] ?? null;
            if ($config && isset($config['access']) && !$this->checkAccess($config)) {
                continue;
            }

            $label = $this->getLabel($config['label'] ?? '', $fieldName);

            $type = $config['type'] ?? '';
            $class = $config['class'] ?? '';
            if ($type !== 'check' && $type !== 'select') {
                $class .= ' form-control';
            }
            if ($type === 'select') {
                $class .= ' form-select';
            }
            $more = '';
            if ($class) {
                $more .= ' class="' . htmlspecialchars($class) . '"';
            }
            $style = $config['style'] ?? '';
            if ($style) {
                $more .= ' style="' . htmlspecialchars($style) . '"';
            }
            if (isset($this->overrideConf[$fieldName])) {
                $more .= ' disabled="disabled"';
            }
            $isBeUsersTable = ($config['table'] ?? false) === 'be_users';
            $value = $isBeUsersTable ? ($backendUser->user[$fieldName] ?? false) : ($backendUser->uc[$fieldName] ?? false);
            if (!$value && isset($config['default'])) {
                $value = $config['default'];
            }
            $dataAdd = $isBeUsersTable ? '[be_users]' : '';

            switch ($type) {
                case 'text':
                case 'number':
                case 'email':
                case 'password':
                    $noAutocomplete = '';

                    $maxLength = $config['max'] ?? 0;
                    if ((int)$maxLength > 0) {
                        $more .= ' maxlength="' . (int)$maxLength . '"';
                    }

                    if ($type === 'password') {
                        $value = '';
                        $noAutocomplete = 'autocomplete="new-password" ';
                    }

                    $addPasswordRequirementsDescription = false;
                    if ($fieldName === 'password' && $this->passwordPolicyValidator->isEnabled() && $this->passwordPolicyValidator->hasRequirements()) {
                        $addPasswordRequirementsDescription = true;
                    }

                    $html = '<input id="field_' . htmlspecialchars($fieldName) . '"
                        type="' . htmlspecialchars($type) . '" ' .
                        ($addPasswordRequirementsDescription ? 'aria-describedby="description_' . htmlspecialchars($fieldName) . '" ' : '') .
                        'name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']" ' .
                        $noAutocomplete .
                        'value="' . htmlspecialchars((string)$value) . '" ' .
                        $more .
                        ' />';

                    if ($addPasswordRequirementsDescription) {
                        $description = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:passwordRequirements.description');
                        $html .= '<div id="description_' . htmlspecialchars($fieldName) . '"><p class="mt-2 mb-1 text-body-secondary">' . htmlspecialchars($description) . '</p>';
                        $html .= '<ul class="mb-0"><li class="text-body-secondary">' . implode('</li><li class="text-body-secondary">', $this->passwordPolicyValidator->getRequirements()) . '</li></ul></div>';
                    }

                    break;
                case 'check':
                    $html = '<input id="field_' . htmlspecialchars($fieldName) . '"
                        type="checkbox"
                        class="form-check-input"
                        name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']"' .
                        ($value ? ' checked="checked"' : '') .
                        $more .
                        ' />';
                    break;
                case 'language':
                    $html = $this->renderLanguageSelect();
                    break;
                case 'select':
                    if ($config['itemsProcFunc'] ?? false) {
                        $html = GeneralUtility::callUserFunction($config['itemsProcFunc'], $config, $this);
                    } else {
                        $html = '<select id="field_' . htmlspecialchars($fieldName) . '"
                            name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']"' .
                            $more . '>' . LF;
                        foreach ($config['items'] as $key => $optionLabel) {
                            $html .= '<option value="' . htmlspecialchars($key) . '"' . ($value == $key ? ' selected="selected"' : '') . '>' . $this->getLabel($optionLabel, '', false) . '</option>' . LF;
                        }
                        $html .= '</select>';
                    }
                    break;
                case 'user':
                    $html = GeneralUtility::callUserFunction($config['userFunc'], $config, $this);
                    break;
                case 'button':
                    $label = $this->getLabel($config['label'] ?? '');
                    if (!empty($config['clickData'])) {
                        $clickData = $config['clickData'];
                        $buttonAttributes = [
                            'type' => 'button',
                            'class' => 'btn btn-default',
                            'value' => $this->getLabel($config['buttonlabel'], '', false),
                        ];
                        if (isset($clickData['eventName'])) {
                            $buttonAttributes['data-event'] = 'click';
                            $buttonAttributes['data-event-name'] = htmlspecialchars($clickData['eventName']);
                            $buttonAttributes['data-event-payload'] = htmlspecialchars($fieldName);
                        }
                        $html = '<input '
                            . GeneralUtility::implodeAttributes($buttonAttributes, false) . ' />';
                    }
                    if (!empty($config['confirm'])) {
                        $confirmData = $config['confirmData'];
                        // cave: values must be processed by `htmlspecialchars()`
                        $buttonAttributes = [
                            'type' => 'button',
                            'class' => 'btn btn-default t3js-modal-trigger',
                            'data-severity' => 'warning',
                            'data-title' => $this->getLabel($config['label'], '', false),
                            'data-bs-content' => $this->getLabel($confirmData['message'], '', false),
                            'value' => htmlspecialchars($this->getLabel($config['buttonlabel'], '', false)),
                        ];
                        if (isset($confirmData['eventName'])) {
                            $buttonAttributes['data-event'] = 'confirm';
                            $buttonAttributes['data-event-name'] = htmlspecialchars($confirmData['eventName']);
                            $buttonAttributes['data-event-payload'] = htmlspecialchars($fieldName);
                        }
                        $html = '<input '
                            . GeneralUtility::implodeAttributes($buttonAttributes, false) . ' />';
                    }
                    break;
                case 'avatar':
                    // Get current avatar image
                    $html = '';
                    $avatarFileUid = $this->getAvatarFileUid((int)$backendUser->user['uid']);

                    if ($avatarFileUid) {
                        $defaultAvatarProvider = GeneralUtility::makeInstance(DefaultAvatarProvider::class);
                        $avatarImage = $defaultAvatarProvider->getImage($backendUser->user, 32);
                        if ($avatarImage) {
                            $icon = '<span class="avatar avatar-size-medium mb-2"><span class="avatar-image">' .
                                '<img alt="" src="' . htmlspecialchars($avatarImage->getUrl()) . '"' .
                                ' width="' . (int)$avatarImage->getWidth() . '"' .
                                ' height="' . (int)$avatarImage->getHeight() . '" />' .
                                '</span></span>';
                            $html .= '<span id="image_' . htmlspecialchars($fieldName) . '">' . $icon . ' </span>';
                        }
                    }
                    $html .= '<input id="field_' . htmlspecialchars($fieldName) . '" type="hidden" ' .
                            'name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']"' . $more .
                            ' value="' . $avatarFileUid . '" data-setup-avatar-field="' . htmlspecialchars($fieldName) . '" />';

                    $html .= '<typo3-formengine-container-files><div class="form-group"><div class="form-group"><div class="form-control-wrap">';
                    $html .= '<button type="button" id="add_button_' . htmlspecialchars($fieldName)
                        . '" class="btn btn-default"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:avatar.openFileBrowser')) . '"'
                        . ' data-setup-avatar-url="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser', ['mode' => 'file', 'bparams' => '|||allowed=' . ($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? '') . ';disallowed=|-0-be_users-avatar-avatar'])) . '"'
                        . '>' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)
                        . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:avatar.openFileBrowser'))
                        . '</button>';
                    if ($avatarFileUid) {
                        // Keep space between both buttons with a whitespace (like for other buttons)
                        $html .= ' ';
                        $html .= '<button type="button" id="clear_button_' . htmlspecialchars($fieldName)
                        . '" class="btn btn-default"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:avatar.clear')) . '" '
                        . '>' . $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)
                        . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:avatar.clear'))
                        . '</button>';
                    }
                    $html .= '</div></div></div></typo3-formengine-container-files>';
                    break;
                case 'mfa':
                    $label = $this->getLabel($config['label'] ?? '');
                    $html = '';
                    $lang = $this->getLanguageService();
                    $hasActiveProviders = $this->mfaProviderRegistry->hasActiveProviders($backendUser);
                    if ($hasActiveProviders) {
                        if ($this->mfaProviderRegistry->hasLockedProviders($backendUser)) {
                            $html .= ' <span class="badge badge-danger">' . htmlspecialchars($lang->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:mfaProviders.lockedMfaProviders')) . '</span>';
                        } else {
                            $html .= ' <span class="badge badge-success">' . htmlspecialchars($lang->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:mfaProviders.enabled')) . '</span>';
                        }
                    }
                    $html .= '<div class="formengine-field-item t3js-formengine-field-item">';
                    $html .= '<div class="form-description">' . nl2br(htmlspecialchars($lang->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:mfaProviders.description'))) . '</div>';
                    if (!$this->mfaProviderRegistry->hasProviders()) {
                        $html .= '<span class="badge badge-danger">' . htmlspecialchars($lang->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:mfaProviders.notAvailable')) . '</span>';
                        break;
                    }
                    $html .= '<div class="form-group"><div class="form-group"><div class="form-control-wrap t3js-file-controls">';
                    $html .= '<a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('mfa')) . '" class="btn btn-default">';
                    $html .=  htmlspecialchars($lang->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:mfaProviders.' . ($hasActiveProviders ? 'manageLinkTitle' : 'setupLinkTitle')));
                    $html .= '</a>';
                    $html .= '</div></div></div></div>';
                    break;
                default:
                    $html = '';
            }

            $htmlPrepended = '';
            $htmlAppended = '';
            if ($type === 'button') {
                $htmlPrepended = '<div class="formengine-field-item t3js-formengine-field-item"><div class="form-group">'
                    . '<div class="form-group"><div class="form-control-wrap t3js-file-controls">';
                $htmlAppended = '</div></div></div></div>';
            }
            if ($type === 'check') {
                $htmlPrepended = '<div class="formengine-field-item t3js-formengine-field-item"><div class="form-wizards-wrap">'
                    . '<div class="form-wizards-element"><div class="form-check form-switch">';
                $htmlAppended = '</div></div></div></div>';
            }
            if ($type === 'select' || $type === 'language') {
                $htmlPrepended = '<div class="formengine-field-item t3js-formengine-field-item"><div class="form-control-wrap">'
                    . '<div class="form-wizards-wrap"><div class="form-wizards-element"><div class="input-group">';
                $htmlAppended = '</div></div></div></div></div>';
            }
            if ($type === 'text' || $type === 'number' || $type === 'email' || $type === 'password') {
                $htmlPrepended = '<div class="formengine-field-item t3js-formengine-field-item"><div class="form-control-wrap">'
                    . '<div class="form-wizards-wrap"><div class="form-wizards-element">';
                $htmlAppended = '</div></div></div></div>';
            }

            $code[] = '<fieldset class="form-section"><div class="row"><div class="form-group col-md-12">'
                . $label
                . $htmlPrepended
                . $html
                . $htmlAppended
                . '</div></div></fieldset>';
        }

        $result[] = [
            'label' => $tabLabel,
            'content' => count($code) ? implode(LF, $code) : '',
        ];
        return $result;
    }

    /**
     * Return a select with available languages.
     * This method is called from the setup module fake TCA userFunc.
     *
     * @return string Complete select as HTML string or warning box if something went wrong.
     */
    protected function renderLanguageSelect()
    {
        $items = $this->locales->getLanguages();
        $officialLanguages = new OfficialLanguages();
        $backendUser = $this->getBackendUser();
        $currentSelectedLanguage = (string)($backendUser->user['lang'] ?? 'default');
        $languageService = $this->getLanguageService();
        $content = '';
        // get all labels in default language as well
        $defaultLanguageLabelService = $this->languageServiceFactory->create('default');
        foreach ($items as $languageCode => $name) {
            if (!$this->locales->isLanguageKeyAvailable($languageCode)) {
                continue;
            }
            $labelIdentifier = $officialLanguages->getLabelIdentifier($languageCode);
            $localizedName = htmlspecialchars($languageService->sL($labelIdentifier) ?: $name);
            $defaultName = $defaultLanguageLabelService->sL($labelIdentifier);
            if ($defaultName === $localizedName || $defaultName === '') {
                $defaultName = $languageCode;
            }
            if ($defaultName !== $languageCode) {
                $defaultName .= ' - ' . $languageCode;
            }
            $localLabel = ' [' . htmlspecialchars($defaultName) . ']';
            $content .= '<option value="' . $languageCode . '"' . ($currentSelectedLanguage === $languageCode ? ' selected="selected"' : '') . '>' . $localizedName . $localLabel . '</option>';
        }
        $content = '<select id="field_lang" name="data[be_users][lang]" class="form-select">' . $content . '</select>';
        if ($currentSelectedLanguage !== 'default' && !@is_dir(Environment::getLabelsPath() . '/' . $currentSelectedLanguage)) {
            $languageUnavailableWarning = htmlspecialchars(sprintf($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:languageUnavailable'), $languageService->sL($officialLanguages->getLabelIdentifier($currentSelectedLanguage)))) . '&nbsp;&nbsp;<br>&nbsp;&nbsp;' . htmlspecialchars($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:languageUnavailable.' . ($backendUser->isAdmin() ? 'admin' : 'user')));
            $content = '<br><span class="badge badge-danger">' . $languageUnavailableWarning . '</span><br><br>' . $content;
        }
        return $content;
    }

    /**
     * Returns a select with all modules for startup.
     * This method is called from the setup module fake TCA userFunc.
     *
     * @return string Complete select as HTML string
     */
    public function renderStartModuleSelect(): string
    {
        // Load available backend modules
        $startModuleSelect = '<option value="">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:startModule.firstInMenu')) . '</option>';
        foreach ($this->moduleProvider->getModules($this->getBackendUser(), false) as $identifier => $module) {
            if ($module->hasSubModules() || $module->isStandalone()) {
                $modules = '';
                if ($module->hasSubModules()) {
                    foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
                        $modules .= '<option value="' . htmlspecialchars($subModuleIdentifier) . '"';
                        $modules .= ($this->getBackendUser()->uc['startModule'] ?? '') === $subModuleIdentifier ? ' selected="selected"' : '';
                        $modules .= '>' . htmlspecialchars($this->getLanguageService()->sL($subModule->getTitle())) . '</option>';
                    }
                } elseif ($module->isStandalone()) {
                    $modules .= '<option value="' . htmlspecialchars($identifier) . '"';
                    $modules .= ($this->getBackendUser()->uc['startModule'] ?? '') === $identifier ? ' selected="selected"' : '';
                    $modules .= '>' . htmlspecialchars($this->getLanguageService()->sL($module->getTitle())) . '</option>';
                }
                $groupLabel = htmlspecialchars($this->getLanguageService()->sL($module->getTitle()));
                $startModuleSelect .= '<optgroup label="' . htmlspecialchars($groupLabel) . '">' . $modules . '</optgroup>';
            }
        }
        return '<select id="field_startModule" name="data[startModule]" class="form-select">' . $startModuleSelect . '</select>';
    }

    /**
     * Returns access check (currently only "admin" is supported)
     *
     * @param array $config Configuration of the field, access mode is defined in key 'access'
     * @return bool Whether it is allowed to modify the given field
     */
    protected function checkAccess(array $config)
    {
        $access = $config['access'];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][$access])) {
            if (class_exists($access)) {
                $accessObject = GeneralUtility::makeInstance($access);
                if (method_exists($accessObject, 'accessLevelCheck')) {
                    // Initialize vars. If method fails, $set will be set to FALSE
                    return $accessObject->accessLevelCheck($config);
                }
            }
        } elseif ($access === 'admin') {
            return $this->getBackendUser()->isAdmin();
        }

        return false;
    }

    /**
     * Returns the label $str from sL() and grays out the value if the $str/$key is found in $this->overrideConf array
     *
     * @param string $str Locallang key
     * @param string $key Alternative override-config key
     * @param bool $addLabelTag Defines whether the string should be wrapped in a <label> tag.
     * @return string HTML output.
     */
    protected function getLabel($str, $key = '', $addLabelTag = true)
    {
        $out = htmlspecialchars($this->getLanguageService()->sL($str));
        if (isset($this->overrideConf[$key ?: $str])) {
            $out = '<span style="color:#999999">' . $out . '</span>';
        }
        if ($addLabelTag) {
            if ($key !== '') {
                $out = '<label class="form-label t3js-formengine-label" for="field_' . htmlspecialchars($key) . '">' . $out . '</label>';
            } else {
                $out = '<label class="form-label t3js-formengine-label">' . $out . '</label>';
            }
        }
        return $out;
    }

    /**
     * Returns array with fields defined in $GLOBALS['TYPO3_USER_SETTINGS']['showitem']
     * Remove fields which are disabled by user TSconfig
     *
     * @return string[] Array with field names visible in form
     */
    protected function getFieldsFromShowItem()
    {
        $allowedFields = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_USER_SETTINGS']['showitem'], true);
        if ($this->getBackendUser()->isAdmin()) {
            // Do not ask for current password if admin (unknown for other users and no security gain)
            $key = array_search('passwordCurrent', $allowedFields);
            if ($key !== false) {
                unset($allowedFields[$key]);
            }
        }

        $backendUser = $this->getBackendUser();
        $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
        if ($backendUser->getOriginalUserIdWhenInSwitchUserMode() && in_array((int)$backendUser->user['uid'], $systemMaintainers, true)) {
            // DataHandler denies changing password of system maintainer users in switch user mode.
            // Do not show the password fields is this case.
            $key = array_search('password', $allowedFields);
            if ($key !== false) {
                unset($allowedFields[$key]);
            }
            $key = array_search('password2', $allowedFields);
            if ($key !== false) {
                unset($allowedFields[$key]);
            }
        }

        foreach ($this->tsFieldConf as $fieldName => $userTsFieldConfig) {
            if (!empty($userTsFieldConfig['disabled'])) {
                $fieldName = rtrim($fieldName, '.');
                $key = array_search($fieldName, $allowedFields);
                if ($key !== false) {
                    unset($allowedFields[$key]);
                }
            }
        }
        return $allowedFields;
    }

    /**
     * Get Avatar fileUid
     *
     * @param int $beUserId
     * @return int
     */
    protected function getAvatarFileUid($beUserId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $file = $queryBuilder
            ->select('uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('be_users')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar')
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($beUserId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$file;
    }

    /**
     * Set avatar fileUid for backend user
     *
     * @param int $beUserId
     * @param numeric-string|''|'delete' $fileUid either a file UID, an empty string, or `delete`
     */
    protected function setAvatarFileUid($beUserId, $fileUid, array &$storeRec)
    {
        // Update is only needed when new fileUid is set
        if ((int)$fileUid === $this->getAvatarFileUid($beUserId)) {
            return;
        }

        // If user is not allowed to modify avatar $fileUid is empty - so don't overwrite existing avatar
        if (empty($fileUid)) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->delete('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('be_users')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar')
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($beUserId, Connection::PARAM_INT)
                )
            )
            ->executeStatement();

        // If Avatar is marked for delete => set it to empty string so it will be updated properly
        if ($fileUid === 'delete') {
            $fileUid = '';
        }

        // Create new reference
        if ((int)$fileUid > 0) {
            // Get file object
            try {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$fileUid);
            } catch (FileDoesNotExistException $e) {
                $file = false;
            }

            // Check if user is allowed to use the image (only when not in simulation mode)
            if ($file && !$file->getStorage()->checkFileActionPermission('read', $file)) {
                $file = false;
            }

            // Check if extension is allowed
            if ($file && $file->isImage()) {
                // Create new file reference
                $storeRec['sys_file_reference']['NEW1234'] = [
                    'uid_local' => (int)$fileUid,
                    'uid_foreign' => (int)$beUserId,
                    'tablenames' => 'be_users',
                    'fieldname' => 'avatar',
                    'pid' => 0,
                ];
                $storeRec['be_users'][(int)$beUserId]['avatar'] = 'NEW1234';
            }
        }
    }

    /**
     * Add FlashMessages for various actions
     */
    protected function addFlashMessages(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();
        if ($this->setupIsUpdated && !$this->settingsAreResetToDefault) {
            $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:setupWasUpdated'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:UserSettings'));
        }
        if ($this->settingsAreResetToDefault) {
            $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:settingsAreReset'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:resetConfiguration'));
        }
        if ($this->setupIsUpdated || $this->settingsAreResetToDefault) {
            $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:activateChanges'), '', ContextualFeedbackSeverity::INFO);
        }
        if ($this->passwordIsSubmitted) {
            switch ($this->passwordIsUpdated) {
                case self::PASSWORD_OLD_WRONG:
                    $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:oldPassword_failed'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword'), ContextualFeedbackSeverity::ERROR);
                    break;
                case self::PASSWORD_NOT_THE_SAME:
                    $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword_failed'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword'), ContextualFeedbackSeverity::ERROR);
                    break;
                case self::PASSWORD_UPDATED:
                    $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword_ok'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword'));
                    break;
                case self::PASSWORD_POLICY_FAILED:
                    $view->addFlashMessage($languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:passwordPolicyFailed'), $languageService->sL('LLL:EXT:setup/Resources/Private/Language/locallang.xlf:newPassword'), ContextualFeedbackSeverity::ERROR);
                    break;
            }
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
