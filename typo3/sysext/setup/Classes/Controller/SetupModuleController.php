<?php

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
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\Setting as SystemLogSettingAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent;

/**
 * Script class for the Setup module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class SetupModuleController
{

    /**
     * Flag if password has not been updated
     */
    const PASSWORD_NOT_UPDATED = 0;

    /**
     * Flag if password has been updated
     */
    const PASSWORD_UPDATED = 1;

    /**
     * Flag if both new passwords do not match
     */
    const PASSWORD_NOT_THE_SAME = 2;

    /**
     * Flag if the current password given was not identical to the real
     * current password
     */
    const PASSWORD_OLD_WRONG = 3;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $overrideConf;

    /**
     * @var bool
     */
    protected $languageUpdate;

    /**
     * @var bool
     */
    protected $pagetreeNeedsRefresh = false;

    /**
     * @var array
     */
    protected $tsFieldConf;

    /**
     * @var bool
     */
    protected $saveData = false;

    /**
     * @var int
     */
    protected $passwordIsUpdated = self::PASSWORD_NOT_UPDATED;

    /**
     * @var bool
     */
    protected $passwordIsSubmitted = false;

    /**
     * @var bool
     */
    protected $setupIsUpdated = false;

    /**
     * @var bool
     */
    protected $settingsAreResetToDefault = false;

    /**
     * Form protection instance
     *
     * @var \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
     */
    protected $formProtection;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'user_setup';

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected EventDispatcherInterface $eventDispatcher;
    protected MfaProviderRegistry $mfaProviderRegistry;
    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected LanguageServiceFactory $languageServiceFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        MfaProviderRegistry $mfaProviderRegistry,
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory,
        LanguageServiceFactory $languageServiceFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->mfaProviderRegistry = $mfaProviderRegistry;
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->languageServiceFactory = $languageServiceFactory;
        // Instantiate the form protection before a simulated user is initialized
        $this->formProtection = FormProtectionFactory::get();
    }

    protected function processAdditionalJavaScriptModules(): void
    {
        $event = new AddJavaScriptModulesEvent();
        /** @var AddJavaScriptModulesEvent $event */
        $event = $this->eventDispatcher->dispatch($event);
        foreach ($event->getModules() as $moduleName) {
            $this->pageRenderer->loadRequireJsModule($moduleName);
        }
    }

    /**
     * Initializes the module for display of the settings form.
     */
    protected function initialize(ServerRequestInterface $request)
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/FormEngine');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Setup/SetupModule');
        $this->processAdditionalJavaScriptModules();
        $this->pageRenderer->addInlineSetting('FormEngine', 'formName', 'editform');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'FormEngine.remainingCharacters' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remainingCharacters'),
        ]);
        $this->getLanguageService()->includeLLFile('EXT:setup/Resources/Private/Language/locallang.xlf');
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('UserSettings'));
        // Getting the 'override' values as set might be set in User TSconfig
        $this->overrideConf = $this->getBackendUser()->getTSConfig()['setup.']['override.'] ?? null;
        // Getting the disabled fields might be set in User TSconfig (eg setup.fields.password.disabled=1)
        $this->tsFieldConf = $this->getBackendUser()->getTSConfig()['setup.']['fields.'] ?? null;
        // id password is disabled, disable repeat of password too (password2)
        if ($this->tsFieldConf['password.']['disabled'] ?? false) {
            $this->tsFieldConf['password2.']['disabled'] = 1;
            $this->tsFieldConf['passwordCurrent.']['disabled'] = 1;
        }
    }

    /**
     * If settings are submitted to _POST[DATA], store them
     * NOTICE: This method is called before the \TYPO3\CMS\Backend\Template\ModuleTemplate
     * is included. See bottom of document.
     *
     * @param array $postData parsed body of the request
     */
    protected function storeIncomingData(array $postData)
    {
        // First check if something is submitted in the data-array from POST vars
        $d = $postData['data'] ?? null;
        $columns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'];
        $backendUser = $this->getBackendUser();
        $beUserId = $backendUser->user['uid'];
        $storeRec = [];
        $fieldList = $this->getFieldsFromShowItem();
        if (is_array($d) && $this->formProtection->validateToken((string)($postData['formToken'] ?? ''), 'BE user setup', 'edit')) {
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
                // Update the real name:
                if (isset($be_user_data['realName']) && $be_user_data['realName'] !== $backendUser->user['realName']) {
                    $backendUser->user['realName'] = ($storeRec['be_users'][$beUserId]['realName'] = substr($be_user_data['realName'], 0, 80));
                }
                // Update the email address:
                if (isset($be_user_data['email']) && $be_user_data['email'] !== $backendUser->user['email']) {
                    $backendUser->user['email'] = ($storeRec['be_users'][$beUserId]['email'] = substr($be_user_data['email'], 0, 255));
                }
                // Update the password:
                if ($passwordIsConfirmed) {
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
                } else {
                    $this->passwordIsUpdated = self::PASSWORD_NOT_THE_SAME;
                }

                $this->setAvatarFileUid($beUserId, $be_user_data['avatar'], $storeRec);

                $this->saveData = true;
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
            if (!empty($storeRec) && $this->saveData) {
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
     * Injects the request object, checks if data should be saved, and prepares a HTML page
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initialize($request);
        if ($request->getMethod() === 'POST') {
            $postData = $request->getParsedBody();
            if (is_array($postData) && !empty($postData)) {
                $this->storeIncomingData($postData);
            }
        }
        if ($this->languageUpdate) {
            $this->content .= $this->buildInstructionDataTag('TYPO3.ModuleMenu.App.refreshMenu');
            $this->content .= $this->buildInstructionDataTag('TYPO3.Backend.Topbar.refresh');
        }
        if ($this->pagetreeNeedsRefresh) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->content .= '<form action="' . $uriBuilder->buildUriFromRoute('user_setup') . '" method="post" id="SetupModuleController" name="usersetup" enctype="multipart/form-data">';
        $this->content .= '<div id="user-setup-wrapper">';
        $this->content .= $this->moduleTemplate->header($this->getLanguageService()->getLL('UserSettings'), false);
        $this->addFlashMessages();

        $formToken = $this->formProtection->generateToken('BE user setup', 'edit');

        // Render the menu items
        $menuItems = $this->renderUserSetup();
        $this->content .= $this->moduleTemplate->getDynamicTabMenu($menuItems, 'user-setup', 1, false, false);
        $this->content .= '<div>';
        $this->content .= '<input type="hidden" name="formToken" value="' . htmlspecialchars($formToken) . '" />
            <input type="hidden" value="1" name="data[save]" />
            <input type="hidden" name="data[setValuesToDefault]" value="0" id="setValuesToDefault" />';
        $this->content .= '</div>';
        // End of wrapper div
        $this->content .= '</div>';
        // Setting up the buttons and markers for docheader
        $this->getButtons();
        // Build the <body> for the module
        // Renders the module page
        $this->content .= '</form>';
        $this->moduleTemplate->setContent($this->content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    protected function buildInstructionDataTag(string $dispatchAction): string
    {
        return sprintf(
            '<typo3-immediate-action action="%s"></typo3-immediate-action>' . "\n",
            htmlspecialchars($dispatchAction)
        );
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('_MOD_user_setup')
            ->setFieldName('');
        $buttonBar->addButton($cshButton);

        $saveButton = $buttonBar->makeInputButton()
            ->setName('data[save]')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setValue('1')
            ->setForm('SetupModuleController')
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));

        $buttonBar->addButton($saveButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->moduleName)
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:setup/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'));
        $buttonBar->addButton($shortcutButton);
    }

    /******************************
     *
     * Render module
     *
     ******************************/

    /**
     * renders the data for all tabs in the user setup and returns
     * everything that is needed with tabs and dyntab menu
     *
     * @return array Ready to use for the dyntabmenu itemarray
     */
    protected function renderUserSetup()
    {
        $backendUser = $this->getBackendUser();
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $html = '';
        $result = [];
        $firstTabLabel = '';
        $code = [];
        $fieldArray = $this->getFieldsFromShowItem();
        $tabLabel = '';
        foreach ($fieldArray as $fieldName) {
            if (strpos($fieldName, '--div--;') === 0) {
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

            // Add field label, wrapped into CSH if available
            $label = '
                <label>
                    ' . $this->getCSH(($config['csh'] ?? false) ?: $fieldName, $this->getLabel($config['label'] ?? '', $fieldName, false), $fieldName) . '
                </label>';

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
                    $html = '<input id="field_' . htmlspecialchars($fieldName) . '"
                        type="' . htmlspecialchars($type) . '"
                        name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']" ' .
                        $noAutocomplete .
                        'value="' . htmlspecialchars($value) . '" ' .
                        $more .
                        ' />';
                    break;
                case 'check':
                    $html = $label . '<div class="form-check form-switch"><input id="field_' . htmlspecialchars($fieldName) . '"
                        type="checkbox"
                        class="form-check-input"
                        name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']"' .
                        ($value ? ' checked="checked"' : '') .
                        $more .
                        ' /></div>';
                    $label = '';
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
                        $html = '<br><input '
                            . GeneralUtility::implodeAttributes($buttonAttributes, false) . ' />';
                    } elseif (!empty($config['onClick'])) {
                        /**
                         * @deprecated Will be removed in TYPO3 v12.0
                         */
                        $onClick = $config['onClick'];
                        if ($config['onClickLabels'] ?? false) {
                            foreach ($config['onClickLabels'] as $key => $labelclick) {
                                $config['onClickLabels'][$key] = $this->getLabel($labelclick, '', false);
                            }
                            $onClick = vsprintf($onClick, $config['onClickLabels']);
                        }
                        $html = '<br><input class="btn btn-default" type="button"
                            aria-labelledby="label_' . htmlspecialchars($fieldName) . '"
                            value="' . $this->getLabel($config['buttonlabel'], '', false) . '"
                            onclick="' . $onClick . '" />';
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
                        if (isset($confirmData['jsCodeAfterOk'])) {
                            /**
                             * @deprecated Will be removed in TYPO3 v12.0
                             */
                            $buttonAttributes['data-href'] = 'javascript:' . htmlspecialchars($confirmData['jsCodeAfterOk']);
                        }
                        $html = '<br><input '
                            . GeneralUtility::implodeAttributes($buttonAttributes, false) . ' />';
                    }
                    break;
                case 'avatar':
                    // Get current avatar image
                    $html = '<br>';
                    $avatarFileUid = $this->getAvatarFileUid($backendUser->user['uid']);

                    if ($avatarFileUid) {
                        $defaultAvatarProvider = GeneralUtility::makeInstance(DefaultAvatarProvider::class);
                        $avatarImage = $defaultAvatarProvider->getImage($backendUser->user, 32);
                        if ($avatarImage) {
                            $icon = '<span class="avatar"><span class="avatar-image">' .
                                '<img alt="" src="' . htmlspecialchars($avatarImage->getUrl()) . '"' .
                                ' width="' . (int)$avatarImage->getWidth() . '" ' .
                                'height="' . (int)$avatarImage->getHeight() . '" />' .
                                '</span></span>';
                            $html .= '<span class="pull-left" style="padding-right: 10px" id="image_' . htmlspecialchars($fieldName) . '">' . $icon . ' </span>';
                        }
                    }
                    $html .= '<input id="field_' . htmlspecialchars($fieldName) . '" type="hidden" ' .
                            'name="data' . $dataAdd . '[' . htmlspecialchars($fieldName) . ']"' . $more .
                            ' value="' . $avatarFileUid . '" data-setup-avatar-field="' . htmlspecialchars($fieldName) . '" />';

                    $html .= '<div class="btn-group">';
                    if ($avatarFileUid) {
                        $html .=
                            '<button type="button" id="clear_button_' . htmlspecialchars($fieldName) . '" aria-label="' . htmlspecialchars($this->getLanguageService()->getLL('avatar.clear')) . '" '
                                . ' class="btn btn-default">'
                                . $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)
                            . '</button>';
                    }
                    $html .=
                        '<button type="button" id="add_button_' . htmlspecialchars($fieldName) . '" class="btn btn-default btn-add-avatar"'
                            . ' aria-label="' . htmlspecialchars($this->getLanguageService()->getLL('avatar.openFileBrowser')) . '"'
                            . ' data-setup-avatar-url="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('wizard_element_browser', ['mode' => 'file', 'bparams' => '||||__IDENTIFIER__'])) . '"'
                            . '>' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL)
                            . '</button></div>';
                    break;
                case 'mfa':
                    $label = $this->getLabel($config['label'] ?? '');
                    $html = '';
                    $lang = $this->getLanguageService();
                    $hasActiveProviders = $this->mfaProviderRegistry->hasActiveProviders($backendUser);
                    if ($hasActiveProviders) {
                        if ($this->mfaProviderRegistry->hasLockedProviders($backendUser)) {
                            $html .= ' <span class="badge badge-danger">' . htmlspecialchars($lang->getLL('mfaProviders.lockedMfaProviders')) . '</span>';
                        } else {
                            $html .= ' <span class="badge badge-success">' . htmlspecialchars($lang->getLL('mfaProviders.enabled')) . '</span>';
                        }
                    }
                    $html .= '<p class="text-muted">' . nl2br(htmlspecialchars($lang->getLL('mfaProviders.description'))) . '</p>';
                    if (!$this->mfaProviderRegistry->hasProviders()) {
                        $html .= '<span class="badge badge-danger">' . htmlspecialchars($lang->getLL('mfaProviders.notAvailable')) . '</span>';
                        break;
                    }
                    $html .= '<a href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('mfa')) . '" class="btn btn-' . ($hasActiveProviders ? 'default' : 'success') . '">';
                    $html .=    $this->iconFactory->getIcon($hasActiveProviders ? 'actions-cog' : 'actions-add', Icon::SIZE_SMALL);
                    $html .=    ' <span>' . htmlspecialchars($lang->getLL('mfaProviders.' . ($hasActiveProviders ? 'manageLinkTitle' : 'setupLinkTitle'))) . '</span>';
                    $html .= '</a>';
                    break;
                default:
                    $html = '';
            }

            $code[] = '<div class="form-section"><div class="row"><div class="form-group t3js-formengine-field-item col-md-12">' .
                $label .
                $html .
                '</div></div></div>';
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
        $tcaConfig = $GLOBALS['TCA']['be_users']['columns']['lang']['config'];
        $items = $tcaConfig['items'];
        $itemsProcFunc = [
            'items' => &$items,
        ];
        GeneralUtility::callUserFunction($tcaConfig['itemsProcFunc'], $itemsProcFunc);
        $backendUser = $this->getBackendUser();
        $currentSelectedLanguage = (string)($backendUser->user['lang'] ?? 'default');
        $languageService = $this->getLanguageService();
        $content = '';
        // get all labels in default language as well
        $defaultLanguageLabelService = $this->languageServiceFactory->create('default');
        $defaultLanguageLabelService->includeLLFile('EXT:setup/Resources/Private/Language/locallang.xlf');
        foreach ($items as $item) {
            $languageCode = $item[1];
            $name = $item[0];
            $available = in_array($languageCode, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? [], true) || is_dir(Environment::getLabelsPath() . '/' . $languageCode);
            if ($available || $languageCode === 'default') {
                $localizedName = htmlspecialchars($languageService->getLL('lang_' . $languageCode) ?: $name);
                $defaultName = $defaultLanguageLabelService->getLL('lang_' . $languageCode);
                if ($defaultName === $localizedName || $defaultName === '') {
                    $defaultName = $languageCode;
                }
                if ($defaultName !== $languageCode) {
                    $defaultName .= ' - ' . $languageCode;
                }
                $localLabel = ' [' . htmlspecialchars($defaultName) . ']';
                $content .= '<option value="' . $languageCode . '"' . ($currentSelectedLanguage === $languageCode ? ' selected="selected"' : '') . '>' . $localizedName . $localLabel . '</option>';
            }
        }
        $content = '<select id="field_lang" name="data[be_users][lang]" class="form-select">' . $content . '</select>';
        if ($currentSelectedLanguage !== 'default' && !@is_dir(Environment::getLabelsPath() . '/' . $currentSelectedLanguage)) {
            $languageUnavailableWarning = htmlspecialchars(sprintf($languageService->getLL('languageUnavailable'), $languageService->getLL('lang_' . $currentSelectedLanguage))) . '&nbsp;&nbsp;<br />&nbsp;&nbsp;' . htmlspecialchars($languageService->getLL('languageUnavailable.' . ($backendUser->isAdmin() ? 'admin' : 'user')));
            $content = '<br /><span class="label label-danger">' . $languageUnavailableWarning . '</span><br /><br />' . $content;
        }
        return $content;
    }

    /**
     * Returns a select with all modules for startup.
     * This method is called from the setup module fake TCA userFunc.
     *
     * @return string Complete select as HTML string
     */
    public function renderStartModuleSelect()
    {
        // Load available backend modules
        $loadModules = GeneralUtility::makeInstance(ModuleLoader::class);
        $loadModules->observeWorkspaces = true;
        $loadModules->load($GLOBALS['TBE_MODULES']);
        $startModuleSelect = '<option value="">' . htmlspecialchars($this->getLanguageService()->getLL('startModule.firstInMenu')) . '</option>';
        foreach ($loadModules->getModules() as $mainMod => $modData) {
            $hasSubmodules = !empty($modData['sub']) && is_array($modData['sub']);
            $isStandalone = $modData['standalone'] ?? false;
            if ($hasSubmodules || $isStandalone) {
                $modules = '';
                if (($hasSubmodules)) {
                    foreach ($modData['sub'] as $subData) {
                        $modName = $subData['name'];
                        $modules .= '<option value="' . htmlspecialchars($modName) . '"';
                        $modules .= ($this->getBackendUser()->uc['startModule'] ?? '') === $modName ? ' selected="selected"' : '';
                        $modules .= '>' . htmlspecialchars($this->getLanguageService()->sL($loadModules->getLabelsForModule($modName)['title'])) . '</option>';
                    }
                } elseif ($isStandalone) {
                    $modName = $modData['name'];
                    $modules .= '<option value="' . htmlspecialchars($modName) . '"';
                    $modules .= ($this->getBackendUser()->uc['startModule'] ?? '') === $modName ? ' selected="selected"' : '';
                    $modules .= '>' . htmlspecialchars($this->getLanguageService()->sL($loadModules->getLabelsForModule($modName)['title'])) . '</option>';
                }
                $groupLabel = htmlspecialchars($this->getLanguageService()->sL($loadModules->getLabelsForModule($mainMod)['title']));
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
     * Returns the label $str from getLL() and grays out the value if the $str/$key is found in $this->overrideConf array
     *
     * @param string $str Locallang key
     * @param string $key Alternative override-config key
     * @param bool $addLabelTag Defines whether the string should be wrapped in a <label> tag.
     * @return string HTML output.
     */
    protected function getLabel($str, $key = '', $addLabelTag = true)
    {
        if (strpos($str, 'LLL:') === 0) {
            $out = htmlspecialchars($this->getLanguageService()->sL($str));
        } else {
            $out = htmlspecialchars($str);
        }
        if (isset($this->overrideConf[$key ?: $str])) {
            $out = '<span style="color:#999999">' . $out . '</span>';
        }
        if ($addLabelTag) {
            if ($key !== '') {
                $out = '<label for="field_' . htmlspecialchars($key) . '">' . $out . '</label>';
            } else {
                $out = '<label>' . $out . '</label>';
            }
        }
        return $out;
    }

    /**
     * Returns the CSH Icon for given string
     *
     * @param string $str Locallang key
     * @param string $label The label to be used, that should be wrapped in help
     * @param string $fieldName field name
     * @return string HTML output.
     */
    protected function getCSH($str, $label, $fieldName)
    {
        $context = '_MOD_user_setup';
        $field = $str;
        $strParts = explode(':', $str);
        if (count($strParts) > 1) {
            // Setting comes from another extension
            $context = $strParts[0];
            $field = $strParts[1];
        } elseif ($str !== 'language' && $str !== 'reset') {
            $field = 'option_' . $str;
        }
        return '<span id="label_' . htmlspecialchars($fieldName) . '">' . BackendUtility::wrapInHelp($context, $field, $label) . '</span>';
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

        if (!is_array($this->tsFieldConf)) {
            return $allowedFields;
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
                    $queryBuilder->createNamedParameter('be_users', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'table_local',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($beUserId, \PDO::PARAM_INT)
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
     * @param array $storeRec
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
                    $queryBuilder->createNamedParameter('be_users', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('avatar', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'table_local',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($beUserId, \PDO::PARAM_INT)
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
                    'table_local' => 'sys_file',
                ];
                $storeRec['be_users'][(int)$beUserId]['avatar'] = 'NEW1234';
            }
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Add FlashMessages for various actions
     */
    protected function addFlashMessages()
    {
        $flashMessages = [];

        // Show if setup was saved
        if ($this->setupIsUpdated && !$this->settingsAreResetToDefault) {
            $flashMessages[] = $this->getFlashMessage('setupWasUpdated', 'UserSettings');
        }

        // Show if temporary data was cleared
        if ($this->settingsAreResetToDefault) {
            $flashMessages[] = $this->getFlashMessage('settingsAreReset', 'resetConfiguration');
        }

        // Notice
        if ($this->setupIsUpdated || $this->settingsAreResetToDefault) {
            $flashMessages[] = $this->getFlashMessage('activateChanges', '', FlashMessage::INFO);
        }

        // If password is updated, output whether it failed or was OK.
        if ($this->passwordIsSubmitted) {
            switch ($this->passwordIsUpdated) {
                case self::PASSWORD_OLD_WRONG:
                    $flashMessages[] = $this->getFlashMessage('oldPassword_failed', 'newPassword', FlashMessage::ERROR);
                    break;
                case self::PASSWORD_NOT_THE_SAME:
                    $flashMessages[] = $this->getFlashMessage('newPassword_failed', 'newPassword', FlashMessage::ERROR);
                    break;
                case self::PASSWORD_UPDATED:
                    $flashMessages[] = $this->getFlashMessage('newPassword_ok', 'newPassword');
                    break;
            }
        }
        if (!empty($flashMessages)) {
            $this->enqueueFlashMessages($flashMessages);
        }
    }

    /**
     * @param array $flashMessages
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function enqueueFlashMessages(array $flashMessages)
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        foreach ($flashMessages as $flashMessage) {
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    /**
     * @param string $message
     * @param string $title
     * @param int $severity
     * @return FlashMessage
     */
    protected function getFlashMessage($message, $title, $severity = FlashMessage::OK)
    {
        $title = !empty($title) ? $this->getLanguageService()->getLL($title) : ' ';
        return GeneralUtility::makeInstance(
            FlashMessage::class,
            $this->getLanguageService()->getLL($message),
            $title,
            $severity
        );
    }
}
