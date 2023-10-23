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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\AST\CommentAwareAstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstConstantCommentVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\Service\LocalConfigurationValueService;

/**
 * Settings controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly CommentAwareAstBuilder $astBuilder,
        private readonly LosslessTokenizer $losslessTokenizer,
        private readonly AstTraverser $astTraverser,
        private readonly FormProtectionFactory $formProtectionFactory,
        private readonly ConfigurationManager $configurationManager,
    ) {}

    /**
     * Main "show the cards" view
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $view->assign('isWritable', $this->configurationManager->canWriteConfiguration());
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/Cards'),
        ]);
    }

    /**
     * Change install tool password
     */
    public function changeInstallToolPasswordGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'changeInstallToolPasswordToken' => $formProtection->generateToken('installTool', 'changeInstallToolPassword'),
        ]);
        $buttons = [];
        if ($isWritable) {
            $buttons[] = [
                'btnClass' => 'btn-default t3js-changeInstallToolPassword-change',
                'text' => 'Set new password',
            ];
        }
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/ChangeInstallToolPassword'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Change install tool password
     */
    public function changeInstallToolPasswordAction(ServerRequestInterface $request): ResponseInterface
    {
        $messageQueue = new FlashMessageQueue('install');
        if (!$this->configurationManager->canWriteConfiguration()) {
            $messageQueue->enqueue(new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            ));
        } else {
            $password = $request->getParsedBody()['install']['password'] ?? '';
            $passwordCheck = $request->getParsedBody()['install']['passwordCheck'];

            if ($password !== $passwordCheck) {
                $messageQueue->enqueue(new FlashMessage(
                    'Given passwords do not match.',
                    'Install tool password not changed',
                    ContextualFeedbackSeverity::ERROR
                ));
            } elseif (strlen($password) < 8) {
                $messageQueue->enqueue(new FlashMessage(
                    'Given password must be at least eight characters long.',
                    'Install tool password not changed',
                    ContextualFeedbackSeverity::ERROR
                ));
            } else {
                $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
                $this->configurationManager->setLocalConfigurationValueByPath(
                    'BE/installToolPassword',
                    $hashInstance->getHashedPassword($password)
                );
                $messageQueue->enqueue(new FlashMessage(
                    'The Install tool password has been changed successfully.',
                    'Install tool password changed'
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Return a list of possible and active system maintainers
     */
    public function systemMaintainerGetListAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'systemMaintainerWriteToken' => $formProtection->generateToken('installTool', 'systemMaintainerWrite'),
            'systemMaintainerIsDevelopmentContext' => Environment::getContext()->isDevelopment(),
        ]);

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // We have to respect the enable fields here by our own because no TCA is loaded
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $users = $queryBuilder
            ->select('uid', 'username', 'disable', 'starttime', 'endtime')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->neq('username', $queryBuilder->createNamedParameter('_cli_'))
                )
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $systemMaintainerList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? [];
        $systemMaintainerList = array_map('intval', $systemMaintainerList);
        $currentTime = time();
        foreach ($users as &$user) {
            $user['disable'] = $user['disable'] ||
                ((int)$user['starttime'] !== 0 && $user['starttime'] > $currentTime) ||
                ((int)$user['endtime'] !== 0 && $user['endtime'] < $currentTime);
            $user['isSystemMaintainer'] = in_array((int)$user['uid'], $systemMaintainerList, true);
        }
        $buttons = [];
        if ($isWritable) {
            $buttons[] = [
                'btnClass' => 'btn-default t3js-systemMaintainer-write',
                'text' => 'Save system maintainer list',
            ];
        }
        return new JsonResponse([
            'success' => true,
            'users' => $users,
            'html' => $view->render('Settings/SystemMaintainer'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Write new system maintainer list
     */
    public function systemMaintainerWriteAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = [];

        if (!$this->configurationManager->canWriteConfiguration()) {
            $messages[] = new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            );
        }
        // Sanitize given user list and write out
        $newUserList = [];
        $users = $request->getParsedBody()['install']['users'] ?? [];
        if (is_array($users)) {
            foreach ($users as $uid) {
                if (MathUtility::canBeInterpretedAsInteger($uid)) {
                    $newUserList[] = (int)$uid;
                }
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();

        $validatedUserList = $queryBuilder
            ->select('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($newUserList, Connection::PARAM_INT_ARRAY))
                )
            )->executeQuery()->fetchAllAssociative();

        $validatedUserList = array_column($validatedUserList, 'uid');
        $validatedUserList = array_map('intval', $validatedUserList);

        $this->configurationManager->setLocalConfigurationValuesByPathValuePairs(
            ['SYS/systemMaintainers' => $validatedUserList]
        );

        if (empty($validatedUserList)) {
            $messages[] = new FlashMessage(
                'The system has no maintainers enabled anymore. Please use the standalone Admin Tools from now on.',
                'Cleared system maintainer list',
                ContextualFeedbackSeverity::INFO
            );
        } else {
            $messages[] = new FlashMessage(
                'New system maintainer uid list: ' . implode(', ', $validatedUserList),
                'Updated system maintainers',
                ContextualFeedbackSeverity::INFO
            );
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Main LocalConfiguration card content
     */
    public function localConfigurationGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $localConfigurationValueService = new LocalConfigurationValueService();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'localConfigurationWriteToken' => $formProtection->generateToken('installTool', 'localConfigurationWrite'),
            'localConfigurationData' => $localConfigurationValueService->getCurrentConfigurationData(),
        ]);

        $buttons = [
            [
                'btnClass' => 'btn-default t3js-localConfiguration-toggleAll',
                'text' => 'Toggle All',
            ],
        ];

        if ($isWritable) {
            $buttons[] = [
                'btnClass' => 'btn-default t3js-localConfiguration-write',
                'text' => 'Write configuration',
            ];
        }

        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/LocalConfigurationGetContent'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Write given LocalConfiguration settings
     *
     * @throws \RuntimeException
     */
    public function localConfigurationWriteAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->configurationManager->canWriteConfiguration()) {
            $messageQueue = new FlashMessageQueue('install');
            $messageQueue->enqueue(new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            ));
        } else {
            $settings = $request->getParsedBody()['install']['configurationValues'];
            if (!is_array($settings) || empty($settings)) {
                throw new \RuntimeException(
                    'Expected value array not found',
                    1502282283
                );
            }
            $localConfigurationValueService = new LocalConfigurationValueService();
            $messageQueue = $localConfigurationValueService->updateLocalConfigurationValues($settings);
            if ($messageQueue->count() === 0) {
                $messageQueue->enqueue(new FlashMessage(
                    'No configuration changes have been detected in the submitted form.',
                    'Configuration not updated',
                    ContextualFeedbackSeverity::WARNING
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Main preset card content
     */
    public function presetsGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $presetFeatures = GeneralUtility::makeInstance(FeatureManager::class);
        $presetFeatures = $presetFeatures->getInitializedFeatures($request->getParsedBody()['install']['values'] ?? []);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'presetsActivateToken' => $formProtection->generateToken('installTool', 'presetsActivate'),
            // This action is called again from within the card itself if a custom image path is supplied
            'presetsGetContentToken' => $formProtection->generateToken('installTool', 'presetsGetContent'),
            'presetFeatures' => $presetFeatures,
        ]);
        $buttons = [];
        if ($isWritable) {
            $buttons[] = [
                'btnClass' => 'btn-default t3js-presets-activate',
                'text' => 'Activate preset',
            ];
        }
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/PresetsGetContent'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Write selected presets
     */
    public function presetsActivateAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = new FlashMessageQueue('install');
        if (!$this->configurationManager->canWriteConfiguration()) {
            $messages->enqueue(new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            ));
        } else {
            $featureManager = new FeatureManager();
            $configurationValues = $featureManager->getConfigurationForSelectedFeaturePresets($request->getParsedBody()['install']['values'] ?? []);
            if (!empty($configurationValues)) {
                $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);
                $messageBody = [];
                foreach ($configurationValues as $configurationKey => $configurationValue) {
                    if (is_array($configurationValue)) {
                        $configurationValue = json_encode($configurationValue);
                    }
                    $messageBody[] = '\'' . $configurationKey . '\' => \'' . $configurationValue . '\'';
                }
                $messages->enqueue(new FlashMessage(
                    implode(', ', $messageBody),
                    'Configuration written'
                ));
            } else {
                $messages->enqueue(new FlashMessage(
                    '',
                    'No configuration change selected',
                    ContextualFeedbackSeverity::INFO
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Render a list of extensions with their configuration form.
     */
    public function extensionConfigurationGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        // Extension configuration needs initialized $GLOBALS['LANG']
        $GLOBALS['LANG'] = $this->languageServiceFactory->create('default');
        $extensionsWithConfigurations = [];
        $activePackages = $this->packageManager->getActivePackages();
        $extensionConfiguration = new ExtensionConfiguration();
        foreach ($activePackages as $extensionKey => $activePackage) {
            if (@file_exists($activePackage->getPackagePath() . 'ext_conf_template.txt')) {
                $ast = $this->astBuilder->build(
                    $this->losslessTokenizer->tokenize(file_get_contents($activePackage->getPackagePath() . 'ext_conf_template.txt')),
                    new RootNode()
                );
                $astConstantCommentVisitor = new (AstConstantCommentVisitor::class);
                $this->astTraverser->traverse($ast, [$astConstantCommentVisitor]);
                $constants = $astConstantCommentVisitor->getConstants();
                // @todo: It would be better to fetch all LocalConfiguration settings of an extension at once
                //        and feed it as pseudo-TS to the AST builder. This way the full AstConstantCommentVisitor
                //        preparation magic would kick in and the JS-side processing in extension-configuration.ts
                //        could be removed (especially the 'wrap' and 'offset' stuff) by handling it in fluid directly.
                foreach ($constants as $constantName => &$constantDetails) {
                    try {
                        $valueFromLocalConfiguration = $extensionConfiguration->get($extensionKey, str_replace('.', '/', $constantName));
                        $constantDetails['value'] = $valueFromLocalConfiguration;
                    } catch (ExtensionConfigurationPathDoesNotExistException $e) {
                        // Deliberately empty - it can happen at runtime that a written config does not return
                        // back all values (eg. saltedpassword with its userFuncs), which then miss in the written
                        // configuration and are only synced after next install tool run. This edge case is
                        // taken care of here.
                    }
                }
                $displayConstants = [];
                foreach ($constants as $constant) {
                    $displayConstants[$constant['cat']][$constant['subcat_sorting_first']]['label'] = $constant['subcat_label'];
                    $displayConstants[$constant['cat']][$constant['subcat_sorting_first']]['items'][$constant['subcat_sorting_second']] = $constant;
                }
                foreach ($displayConstants as &$constantCategory) {
                    ksort($constantCategory);
                    foreach ($constantCategory as &$constantDetailItems) {
                        ksort($constantDetailItems['items']);
                    }
                }
                $extensionsWithConfigurations[$extensionKey] = $displayConstants;
            }
        }
        ksort($extensionsWithConfigurations);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'extensionsWithConfigurations' => $extensionsWithConfigurations,
            'extensionConfigurationWriteToken' => $formProtection->generateToken('installTool', 'extensionConfigurationWrite'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/ExtensionConfigurationGetContent'),
        ]);
    }

    /**
     * Write extension configuration
     */
    public function extensionConfigurationWriteAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = [];
        if (!$this->configurationManager->canWriteConfiguration()) {
            $messages[] = new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            );
        } else {
            $extensionKey = $request->getParsedBody()['install']['extensionKey'];
            $configuration = $request->getParsedBody()['install']['extensionConfiguration'] ?? [];
            $nestedConfiguration = [];
            foreach ($configuration as $configKey => $value) {
                $nestedConfiguration = ArrayUtility::setValueByPath($nestedConfiguration, $configKey, $value, '.');
            }
            (new ExtensionConfiguration())->set($extensionKey, $nestedConfiguration);
            $messages[] = new FlashMessage(
                'Successfully saved configuration for extension "' . $extensionKey . '".',
                'Configuration saved',
                ContextualFeedbackSeverity::OK
            );
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Render feature toggles
     */
    public function featuresGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $isWritable = $this->configurationManager->canWriteConfiguration();
        $configurationDescription = GeneralUtility::makeInstance(YamlFileLoader::class)
            ->load($this->configurationManager->getDefaultConfigurationDescriptionFileLocation());
        $allFeatures = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features'] ?? [];
        $features = [];
        foreach ($allFeatures as $featureName => $featureValue) {
            // Only features that have a .yml description will be listed. There is currently no
            // way for extensions to extend this, so feature toggles of non-core extensions are
            // not listed here.
            if (isset($configurationDescription['SYS']['items']['features']['items'][$featureName]['description'])) {
                $default = $this->configurationManager->getDefaultConfigurationValueByPath('SYS/features/' . $featureName);
                $features[] = [
                    'label' => ucfirst(str_replace(['_', '.'], ' ', strtolower(GeneralUtility::camelCaseToLowerCaseUnderscored(preg_replace('/\./', ': ', $featureName, 1))))),
                    'name' => $featureName,
                    'description' => $configurationDescription['SYS']['items']['features']['items'][$featureName]['description'],
                    'default' => $default,
                    'value' => $featureValue,
                ];
            }
        }
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'isWritable' => $isWritable,
            'features' => $features,
            'featuresSaveToken' => $formProtection->generateToken('installTool', 'featuresSave'),
        ]);
        $buttons = [];
        if ($isWritable) {
            $buttons[] =  [
                'btnClass' => 'btn-default t3js-features-save',
                'text' => 'Save',
            ];
        }
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Settings/FeaturesGetContent'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Update feature toggles state
     */
    public function featuresSaveAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->configurationManager->canWriteConfiguration()) {
            $message = new FlashMessage(
                'The configuration file is not writable.',
                'Configuration not writable',
                ContextualFeedbackSeverity::ERROR
            );
        } else {
            $enabledFeaturesFromPost = $request->getParsedBody()['install']['values'] ?? [];
            $allFeatures = array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['features'] ?? []);
            $configurationDescription = GeneralUtility::makeInstance(YamlFileLoader::class)
                ->load($this->configurationManager->getDefaultConfigurationDescriptionFileLocation());
            $updatedFeatures = [];
            $configurationPathValuePairs = [];
            foreach ($allFeatures as $featureName) {
                // Only features that have a .yml description will be listed. There is currently no
                // way for extensions to extend this, so feature toggles of non-core extensions are
                // not considered.
                if (isset($configurationDescription['SYS']['items']['features']['items'][$featureName]['description'])) {
                    $path = 'SYS/features/' . $featureName;
                    $newValue = isset($enabledFeaturesFromPost[$featureName]);
                    if ($newValue !== $this->configurationManager->getConfigurationValueByPath($path)) {
                        $configurationPathValuePairs[$path] = $newValue;
                        $updatedFeatures[] = $featureName . ' [' . ($newValue ? 'On' : 'Off') . ']';
                    }
                }
            }
            if ($configurationPathValuePairs !== []) {
                $success = $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
                if ($success) {
                    $this->configurationManager->exportConfiguration();
                    $message = new FlashMessage(
                        "Successfully updated the following feature toggles:\n" . implode(",\n", $updatedFeatures),
                        'Features updated',
                        ContextualFeedbackSeverity::OK
                    );
                } else {
                    $message = new FlashMessage(
                        'An error occurred while saving. Some settings may not have been updated.',
                        'Features not updated',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            } else {
                $message = new FlashMessage(
                    'Nothing to update.',
                    'Features not updated',
                    ContextualFeedbackSeverity::INFO
                );
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => [$message],
        ]);
    }
}
