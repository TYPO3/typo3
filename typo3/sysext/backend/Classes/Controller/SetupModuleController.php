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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Configuration\UserSettingsTcaConfiguration;
use TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\UserSettingsDataGroup;
use TYPO3\CMS\Backend\Form\FormResultFactory;
use TYPO3\CMS\Backend\Form\FormResultHandler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Enum\ModuleLayout;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\SysLog\Action\Setting as SystemLogSettingAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent;

/**
 * Script class for the User Settings module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class SetupModuleController
{
    protected const PASSWORD_NOT_UPDATED = 0;
    protected const PASSWORD_UPDATED = 1;
    protected const PASSWORD_NOT_THE_SAME = 2;
    // @todo: Can this constant be removed?
    protected const PASSWORD_OLD_WRONG = 3;
    protected const PASSWORD_POLICY_FAILED = 4;

    protected array $overrideConf = [];
    protected bool $languageUpdate = false;
    protected array $persistentUpdate = [];
    protected bool $pagetreeNeedsRefresh = false;
    protected bool $colorSchemeChanged = false;
    protected bool $themeChanged = false;
    protected bool $backendTitleFormatChanged = false;
    protected bool $dateTimeFirstDayOfWeekChanged = false;

    protected array $tsFieldConf = [];
    protected int $passwordIsUpdated = self::PASSWORD_NOT_UPDATED;
    protected bool $passwordIsSubmitted = false;
    protected bool $setupIsUpdated = false;
    protected bool $settingsAreResetToDefault = false;

    protected PasswordPolicyValidator $passwordPolicyValidator;

    public function __construct(
        protected readonly Typo3Information $typo3Information,
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
        protected readonly ComponentFactory $componentFactory,
        protected readonly DateFormatter $dateFormatter,
        protected readonly FormDataCompiler $formDataCompiler,
        protected readonly NodeFactory $nodeFactory,
        protected readonly FormResultFactory $formResultFactory,
        protected readonly FormResultHandler $formResultHandler,
        protected readonly UserSettingsTcaConfiguration $userSettingsTcaConfiguration,
        protected readonly UserSettingsSchema $userSettingsSchema,
        protected readonly TcaSchemaBuilder $tcaSchemaBuilder,
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
        if ($this->pagetreeNeedsRefresh || $this->settingsAreResetToDefault) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        if ($this->colorSchemeChanged || $this->settingsAreResetToDefault) {
            BackendUtility::setUpdateSignal('updateColorScheme', $this->getBackendUser()->uc['colorScheme'] ?? 'auto');
        }
        if ($this->themeChanged || $this->settingsAreResetToDefault) {
            BackendUtility::setUpdateSignal('updateTheme', $this->getBackendUser()->uc['theme'] ?? 'modern');
        }
        if ($this->backendTitleFormatChanged || $this->settingsAreResetToDefault) {
            BackendUtility::setUpdateSignal('updateTitleFormat', $this->getBackendUser()->uc['backendTitleFormat'] ?? 'titleFirst');
        }
        if ($this->dateTimeFirstDayOfWeekChanged || $this->settingsAreResetToDefault) {
            BackendUtility::setUpdateSignal('updateDateTimeFirstDayOfWeek', $this->getBackendUser()->uc['dateTimeFirstDayOfWeek'] ?? '');
        }
        if ($this->languageUpdate) {
            $this->getLanguageService()->init($this->getBackendUser()->user['lang'] ?? 'en');
            $locale = $this->getLanguageService()->getLocale();
            if ($locale !== null) {
                $parameters = [
                    'language' => $locale->getLanguageCode(),
                ];
                BackendUtility::setUpdateSignal('updateBackendLanguage', $parameters);
            }
        }
        if ($this->persistentUpdate !== []) {
            foreach ($this->persistentUpdate as $params) {
                BackendUtility::setUpdateSignal('updatePersistent', $params);
            }
        }

        // Use FormEngine to render the user settings form
        $formData = $this->compileFormData($request, $this->userSettingsTcaConfiguration->getTca());
        $formData['renderType'] = 'fullRecordContainer';
        $formResultArray = $this->nodeFactory->create($formData)->render();
        // Needed to be set for 'onChange="reload"' and reload on type change to work
        $formResultArray['doSaveFieldName'] = 'doSave';
        $formResult = $this->formResultFactory->create($formResultArray);
        $this->formResultHandler->addAssets($formResult);

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $this->addFlashMessages($view);
        $view->addButtonToButtonBar($this->componentFactory->createSaveButton('SetupModuleController')->setName('data[save]'));
        $this->registerResetButtonToButtonBar($view);
        // Set shortcut context - reload button is added automatically
        $view->getDocHeaderComponent()->setShortcutContext(
            'user_setup',
            $this->getLanguageService()->translate('short_description', 'backend.modules.user_settings')
        );
        $view->assignMultiple([
            'typo3Info' => $this->typo3Information,
            'isLanguageUpdate' => $this->languageUpdate,
            'formEngineHtml' => $formResult->html,
            'formEngineFooter' => implode(LF, $formResult->hiddenFieldsHtml),
            'formToken' => $formProtection->generateToken('BE user setup', 'edit'),
        ]);
        return $view->renderResponse('Setup/Main');
    }

    /**
     * Compile form data for FormEngine rendering.
     */
    protected function compileFormData(ServerRequestInterface $request, array $userSettingsTca): array
    {
        $backendUser = $this->getBackendUser();
        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => 'be_users_settings',
            'vanillaUid' => (int)$backendUser->user['uid'],
            'command' => 'edit',
            'returnUrl' => '',
            'tcaSchemata' => $this->tcaSchemaBuilder->buildFromStructure($userSettingsTca),
            'fullTca' => $userSettingsTca,
        ];
        return $this->formDataCompiler->compile(
            $formDataCompilerInput,
            GeneralUtility::makeInstance(UserSettingsDataGroup::class)
        );
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
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/setup-module.js');
        $this->processAdditionalJavaScriptModules($request);
        $this->pageRenderer->addInlineSetting('FormEngine', 'formName', 'editform');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'FormEngine.remainingCharacters' => $languageService->translate('labels.remainingCharacters', 'core.core'),
        ]);
        $view->setTitle($languageService->translate('user_settings', 'backend.user_profile'));
        $view->setLayout(ModuleLayout::NORMAL);
        // Getting the 'override' values as set might be set in user TSconfig
        $this->overrideConf = $backendUser->getTSConfig()['setup.']['override.'] ?? [];
        // Getting the disabled fields might be set in user TSconfig (eg setup.fields.password.disabled=1)
        $this->tsFieldConf = $backendUser->getTSConfig()['setup.']['fields.'] ?? [];
        // if password is disabled, disable repeat of password too (password2)
        if ($this->tsFieldConf['password.']['disabled'] ?? false) {
            $this->tsFieldConf['password2.']['disabled'] = 1;
        }
        return $view;
    }

    protected function processAdditionalJavaScriptModules(ServerRequestInterface $request): void
    {
        // @deprecated since TYPO3 v14, remove this dispatch with TYPO3 v15
        $legacyEvent = new AddJavaScriptModulesEvent($request);
        $legacyEvent = $this->eventDispatcher->dispatch($legacyEvent);
        foreach ($legacyEvent->getJavaScriptModules() as $specifier) {
            $this->pageRenderer->loadJavaScriptModule($specifier);
        }

        $event = new AddUserSettingsJavaScriptModulesEvent($request);
        $event = $this->eventDispatcher->dispatch($event);
        foreach ($event->getJavaScriptModules() as $specifier) {
            $this->pageRenderer->loadJavaScriptModule($specifier);
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
        $d = $postData['data']['be_users_settings'][(int)$this->getBackendUser()->user['uid']] ?? null;
        $columns = $this->userSettingsSchema->getColumns();
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
            if (isset($d['lang']) && $d['lang'] !== $backendUser->user['lang']) {
                $this->languageUpdate = true;
            }
            // Reload pagetree if the title length is changed
            if (isset($d['titleLen']) && $d['titleLen'] !== $backendUser->uc['titleLen']) {
                $this->pagetreeNeedsRefresh = true;
            }
            if (isset($d['colorScheme']) && $d['colorScheme'] !== ($backendUser->uc['colorScheme'] ?? null)) {
                $this->colorSchemeChanged = true;
            }
            if (isset($d['theme']) && $d['theme'] !== ($backendUser->uc['theme'] ?? null)) {
                $this->themeChanged = true;
            }
            if (isset($d['backendTitleFormat']) && $d['backendTitleFormat'] !== ($backendUser->uc['backendTitleFormat'] ?? null)) {
                $this->backendTitleFormatChanged = true;
            }
            if (isset($d['dateTimeFirstDayOfWeek']) && $d['dateTimeFirstDayOfWeek'] !== ($backendUser->uc['dateTimeFirstDayOfWeek'] ?? null)) {
                $this->dateTimeFirstDayOfWeekChanged = true;
                $this->persistentUpdate[] = [
                    'fieldName' => 'dateTimeFirstDayOfWeek',
                    'value' => $d['dateTimeFirstDayOfWeek'],
                ];
            }
            // Options which should trigger direct JS persistent update, because
            // their new state needs to be available in JS components right away.
            foreach ($this->userSettingsSchema->getPersistentUpdateFieldNames() as $fieldName) {
                $fieldValue = ((int)($d[$fieldName] ?? 0)) ? 'on' : 0;
                if ($fieldValue !== ($backendUser->uc[$fieldName] ?? null)) {
                    $this->persistentUpdate[] = [
                        'fieldName' => $fieldName,
                        'value' => $fieldValue ? '1' : '0',
                    ];
                }
            }

            if ($d['setValuesToDefault'] ?? $postData['data']['setValuesToDefault'] ?? false) {
                // If every value should be default
                $backendUser->resetUC();
                $this->settingsAreResetToDefault = true;
            } elseif ($d['save'] ?? $postData['data']['save'] ?? $postData['doSave'] ?? false) {
                foreach ($columns as $field => $config) {
                    if (!in_array($field, $fieldList, true)) {
                        continue;
                    }
                    $isBeUsersField = ($config['table'] ?? '') === 'be_users';
                    $fieldType = $config['type'] ?? 'text';
                    if ($isBeUsersField && !in_array($field, ['password', 'password2', 'email', 'realName', 'admin', 'avatar'], true)) {
                        $submittedValue = $d[$field] ?? null;
                        if (!isset($config['access']) || ($this->checkAccess($config) && ($backendUser->user[$field] !== $submittedValue))) {
                            if ($fieldType === 'check') {
                                $fieldValue = (int)($d[$field] ?? 0);
                            } else {
                                $fieldValue = $submittedValue;
                            }
                            $storeRec['be_users'][$beUserId][$field] = $fieldValue;
                            $backendUser->user[$field] = $fieldValue;
                        }
                    }
                    if ($fieldType === 'check') {
                        $backendUser->uc[$field] = (int)($d[$field] ?? 0);
                    } else {
                        $backendUser->uc[$field] = htmlspecialchars($d[$field] ?? '');
                    }
                }
                // Personal data for the users be_user-record (email, name, password...)
                // If email and name is changed, set it in the users record:
                $be_user_data = $d;
                // Possibility to modify the transmitted values. Useful to do transformations, like RSA password decryption
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'] ?? [] as $function) {
                    $params = ['be_user_data' => &$be_user_data];
                    GeneralUtility::callUserFunction($function, $params, $this);
                }
                $this->passwordIsSubmitted = (string)($be_user_data['password'] ?? '') !== '';
                $passwordIsConfirmed = $this->passwordIsSubmitted && $be_user_data['password'] === $be_user_data['password2'];

                // Validate password against password policy
                $contextData = new ContextData(
                    loginMode: 'BE',
                    currentPasswordHash: $this->getBackendUser()->user['password'],
                    newUserFullName: $be_user_data['realName'] ?? $this->getBackendUser()->user['realName']
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
                if ($this->passwordIsSubmitted) {
                    if ($passwordIsConfirmed && $passwordValid) {
                        $this->passwordIsUpdated = self::PASSWORD_UPDATED;
                        $storeRec['be_users'][$beUserId]['password'] = $be_user_data['password'];
                    } elseif ($passwordIsConfirmed) {
                        $this->passwordIsUpdated = self::PASSWORD_POLICY_FAILED;
                    } else {
                        $this->passwordIsUpdated = self::PASSWORD_NOT_THE_SAME;
                    }
                }

                $this->setAvatarFileUid($beUserId, $be_user_data['avatar'] ?? null, $storeRec);

                $doSaveData = true;
            }
            // Inserts the overriding values.
            $backendUser->overrideUC();
            $save_after = md5(serialize($backendUser->uc));
            // If something in the uc-array of the user has changed, we save the array...
            if ($save_before != $save_after) {
                $backendUser->writeUC();
                $backendUser->writelog(SystemLogType::SETTING, SystemLogSettingAction::CHANGE, SystemLogErrorClassification::MESSAGE, null, 'Personal settings changed', []);
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
                $dataHandler->process_datamap();
                $dataHandler->printLogErrorMessages();
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
     * Returns array with fields defined in TCA user settings showitem.
     * Remove fields which are disabled by user TSconfig
     *
     * @return string[] Array with field names visible in form
     */
    protected function getFieldsFromShowItem(): array
    {
        $allowedFields = GeneralUtility::trimExplode(',', $this->userSettingsSchema->getShowitem(), true);
        $backendUser = $this->getBackendUser();
        if ($backendUser->getOriginalUserIdWhenInSwitchUserMode() && $backendUser->isSystemMaintainer(true)) {
            // DataHandler denies changing the password of system maintainer users in switch user mode.
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
     */
    protected function getAvatarFileUid(int $beUserId): int
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
     * @param numeric-string|''|'delete'|null $fileUid either null, a file UID, an empty string, or `delete`
     */
    protected function setAvatarFileUid(int $beUserId, ?string $fileUid, array &$storeRec): void
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
     * Register the reset configuration button to the button bar.
     */
    protected function registerResetButtonToButtonBar(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();
        $resetButton = $this->componentFactory->createGenericButton()
            ->setTag('button')
            ->setLabel($languageService->translate('reset_configuration_button', 'backend.user_profile'))
            ->setTitle($languageService->translate('reset_configuration', 'backend.user_profile'))
            ->setIcon($this->iconFactory->getIcon('actions-undo', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setClasses('t3js-modal-trigger')
            ->setAttributes([
                'type' => 'button',
                'data-severity' => 'warning',
                'data-title' => $languageService->translate('reset_configuration', 'backend.user_profile'),
                'data-content' => $languageService->translate('set_to_standard_question', 'backend.user_profile'),
                'data-event' => 'confirm',
                'data-event-name' => 'setup:confirmation:response',
                'data-event-payload' => 'reset_configuration',
            ]);
        $view->addButtonToButtonBar($resetButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Add FlashMessages for various actions
     */
    protected function addFlashMessages(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();
        if ($this->setupIsUpdated && !$this->settingsAreResetToDefault) {
            $view->addFlashMessage($languageService->translate('setup_was_updated', 'backend.user_profile'), $languageService->translate('user_settings', 'backend.user_profile'));
        }
        if ($this->settingsAreResetToDefault) {
            $view->addFlashMessage($languageService->translate('settings_are_reset', 'backend.user_profile'), $languageService->translate('reset_configuration', 'backend.user_profile'));
        }
        if ($this->passwordIsSubmitted) {
            switch ($this->passwordIsUpdated) {
                case self::PASSWORD_NOT_THE_SAME:
                    $view->addFlashMessage($languageService->translate('new_password_failed', 'backend.user_profile'), $languageService->translate('new_password', 'backend.user_profile'), ContextualFeedbackSeverity::ERROR);
                    break;
                case self::PASSWORD_UPDATED:
                    $view->addFlashMessage($languageService->translate('new_password_ok', 'backend.user_profile'), $languageService->translate('new_password', 'backend.user_profile'));
                    break;
                case self::PASSWORD_POLICY_FAILED:
                    $view->addFlashMessage($languageService->translate('password_policy_failed', 'backend.user_profile'), $languageService->translate('new_password', 'backend.user_profile'), ContextualFeedbackSeverity::ERROR);
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
