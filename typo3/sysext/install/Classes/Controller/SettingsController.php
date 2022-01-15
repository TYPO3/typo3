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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\Service\ExtensionConfigurationService;
use TYPO3\CMS\Install\Service\LocalConfigurationValueService;

/**
 * Settings controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class SettingsController extends AbstractController
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var ExtensionConfigurationService
     */
    private $extensionConfigurationService;

    /**
     * @var LanguageServiceFactory
     */
    private $languageServiceFactory;

    public function __construct(
        PackageManager $packageManager,
        ExtensionConfigurationService $extensionConfigurationService,
        LanguageServiceFactory $languageServiceFactory
    ) {
        $this->packageManager = $packageManager;
        $this->extensionConfigurationService = $extensionConfigurationService;
        $this->languageServiceFactory = $languageServiceFactory;
    }

    /**
     * Main "show the cards" view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Settings/Cards.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Change install tool password
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function changeInstallToolPasswordGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Settings/ChangeInstallToolPassword.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'changeInstallToolPasswordToken' => $formProtection->generateToken('installTool', 'changeInstallToolPassword'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-changeInstallToolPassword-change',
                    'text' => 'Set new password',
                ],
            ],
        ]);
    }

    /**
     * Change install tool password
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function changeInstallToolPasswordAction(ServerRequestInterface $request): ResponseInterface
    {
        $password = $request->getParsedBody()['install']['password'] ?? '';
        $passwordCheck = $request->getParsedBody()['install']['passwordCheck'];
        $messageQueue = new FlashMessageQueue('install');

        if ($password !== $passwordCheck) {
            $messageQueue->enqueue(new FlashMessage(
                'Given passwords do not match.',
                'Install tool password not changed',
                FlashMessage::ERROR
            ));
        } elseif (strlen($password) < 8) {
            $messageQueue->enqueue(new FlashMessage(
                'Given password must be at least eight characters long.',
                'Install tool password not changed',
                FlashMessage::ERROR
            ));
        } else {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            $configurationManager->setLocalConfigurationValueByPath(
                'BE/installToolPassword',
                $hashInstance->getHashedPassword($password)
            );
            $messageQueue->enqueue(new FlashMessage(
                'The Install tool password has been changed successfully.',
                'Install tool password changed'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Return a list of possible and active system maintainers
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function systemMaintainerGetListAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Settings/SystemMaintainer.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
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
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->neq('username', $queryBuilder->createNamedParameter('_cli_', \PDO::PARAM_STR))
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
        return new JsonResponse([
            'success' => true,
            'users' => $users,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-systemMaintainer-write',
                    'text' => 'Save system maintainer list',
                ],
            ],
        ]);
    }

    /**
     * Write new system maintainer list
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function systemMaintainerWriteAction(ServerRequestInterface $request): ResponseInterface
    {
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
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($newUserList, Connection::PARAM_INT_ARRAY))
                )
            )->executeQuery()->fetchAllAssociative();

        $validatedUserList = array_column($validatedUserList, 'uid');
        $validatedUserList = array_map('intval', $validatedUserList);

        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs(
            ['SYS/systemMaintainers' => $validatedUserList]
        );

        $messages = [];
        if (empty($validatedUserList)) {
            $messages[] = new FlashMessage(
                'The system has no maintainers enabled anymore. Please use the standalone Admin Tools from now on.',
                'Cleared system maintainer list',
                FlashMessage::INFO
            );
        } else {
            $messages[] = new FlashMessage(
                'New system maintainer uid list: ' . implode(', ', $validatedUserList),
                'Updated system maintainers',
                FlashMessage::INFO
            );
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Main LocalConfiguration card content
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function localConfigurationGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $localConfigurationValueService = new LocalConfigurationValueService();
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view = $this->initializeStandaloneView($request, 'Settings/LocalConfigurationGetContent.html');
        $view->assignMultiple([
            'localConfigurationWriteToken' => $formProtection->generateToken('installTool', 'localConfigurationWrite'),
            'localConfigurationData' => $localConfigurationValueService->getCurrentConfigurationData(),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-localConfiguration-write',
                    'text' => 'Write configuration',
                ],
                [
                    'btnClass' => 'btn-default t3js-localConfiguration-toggleAll',
                    'text' => 'Toggle All',
                ],
            ],
        ]);
    }

    /**
     * Write given LocalConfiguration settings
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function localConfigurationWriteAction(ServerRequestInterface $request): ResponseInterface
    {
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
                FlashMessage::WARNING
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Main preset card content
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function presetsGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Settings/PresetsGetContent.html');
        $presetFeatures = GeneralUtility::makeInstance(FeatureManager::class);
        $presetFeatures = $presetFeatures->getInitializedFeatures($request->getParsedBody()['install']['values'] ?? []);
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'presetsActivateToken' => $formProtection->generateToken('installTool', 'presetsActivate'),
            // This action is called again from within the card itself if a custom image path is supplied
            'presetsGetContentToken' => $formProtection->generateToken('installTool', 'presetsGetContent'),
            'presetFeatures' => $presetFeatures,
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-presets-activate',
                    'text' => 'Activate preset',
                ],
            ],
        ]);
    }

    /**
     * Write selected presets
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function presetsActivateAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = new FlashMessageQueue('install');
        $configurationManager = new ConfigurationManager();
        $featureManager = new FeatureManager();
        $configurationValues = $featureManager->getConfigurationForSelectedFeaturePresets($request->getParsedBody()['install']['values'] ?? []);
        if (!empty($configurationValues)) {
            $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);
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
                FlashMessage::INFO
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Render a list of extensions with their configuration form.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionConfigurationGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        // Extension configuration needs initialized $GLOBALS['LANG']
        $GLOBALS['LANG'] = $this->languageServiceFactory->create('default');
        $extensionsWithConfigurations = [];
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $extensionKey => $activePackage) {
            if (@file_exists($activePackage->getPackagePath() . 'ext_conf_template.txt')) {
                $extensionsWithConfigurations[$extensionKey] = [
                    'packageInfo' => $activePackage,
                    'configuration' => $this->extensionConfigurationService->getConfigurationPreparedForView($extensionKey),
                ];
            }
        }
        ksort($extensionsWithConfigurations);
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view = $this->initializeStandaloneView($request, 'Settings/ExtensionConfigurationGetContent.html');
        $view->assignMultiple([
            'extensionsWithConfigurations' => $extensionsWithConfigurations,
            'extensionConfigurationWriteToken' => $formProtection->generateToken('installTool', 'extensionConfigurationWrite'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Write extension configuration
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionConfigurationWriteAction(ServerRequestInterface $request): ResponseInterface
    {
        $extensionKey = $request->getParsedBody()['install']['extensionKey'];
        $configuration = $request->getParsedBody()['install']['extensionConfiguration'] ?? [];
        $nestedConfiguration = [];
        foreach ($configuration as $configKey => $value) {
            $nestedConfiguration = ArrayUtility::setValueByPath($nestedConfiguration, $configKey, $value, '.');
        }
        (new ExtensionConfiguration())->set($extensionKey, $nestedConfiguration);
        $messages = [
            new FlashMessage(
                'Successfully saved configuration for extension "' . $extensionKey . '".',
                'Configuration saved',
                FlashMessage::OK
            ),
        ];
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Render feature toggles
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function featuresGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configurationDescription = GeneralUtility::makeInstance(YamlFileLoader::class)
            ->load($configurationManager->getDefaultConfigurationDescriptionFileLocation());
        $allFeatures = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features'] ?? [];
        $features = [];
        foreach ($allFeatures as $featureName => $featureValue) {
            // Only features that have a .yml description will be listed. There is currently no
            // way for extensions to extend this, so feature toggles of non-core extensions are
            // not listed here.
            if (isset($configurationDescription['SYS']['items']['features']['items'][$featureName]['description'])) {
                $default = $configurationManager->getDefaultConfigurationValueByPath('SYS/features/' . $featureName);
                $features[] = [
                    'label' => ucfirst(str_replace(['_', '.'], ' ', strtolower(GeneralUtility::camelCaseToLowerCaseUnderscored(preg_replace('/\./', ': ', $featureName, 1))))),
                    'name' => $featureName,
                    'description' => $configurationDescription['SYS']['items']['features']['items'][$featureName]['description'],
                    'default' => $default,
                    'value' => $featureValue,
                ];
            }
        }
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view = $this->initializeStandaloneView($request, 'Settings/FeaturesGetContent.html');
        $view->assignMultiple([
            'features' => $features,
            'featuresSaveToken' => $formProtection->generateToken('installTool', 'featuresSave'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-features-save',
                    'text' => 'Save',
                ],
            ],
        ]);
    }

    /**
     * Update feature toggles state
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function featuresSaveAction(ServerRequestInterface $request): ResponseInterface
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $enabledFeaturesFromPost = $request->getParsedBody()['install']['values'] ?? [];
        $allFeatures = array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['features'] ?? []);
        $configurationDescription = GeneralUtility::makeInstance(YamlFileLoader::class)
            ->load($configurationManager->getDefaultConfigurationDescriptionFileLocation());
        $updatedFeatures = [];
        $configurationPathValuePairs = [];
        foreach ($allFeatures as $featureName) {
            // Only features that have a .yml description will be listed. There is currently no
            // way for extensions to extend this, so feature toggles of non-core extensions are
            // not considered.
            if (isset($configurationDescription['SYS']['items']['features']['items'][$featureName]['description'])) {
                $path = 'SYS/features/' . $featureName;
                $newValue = isset($enabledFeaturesFromPost[$featureName]);
                if ($newValue !== $configurationManager->getConfigurationValueByPath($path)) {
                    $configurationPathValuePairs[$path] = $newValue;
                    $updatedFeatures[] = $featureName . ' [' . ($newValue ? 'On' : 'Off') . ']';
                }
            }
        }
        if ($configurationPathValuePairs !== []) {
            $success = $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
            if ($success) {
                $configurationManager->exportConfiguration();
                $message = "Successfully updated the following feature toggles:\n" . implode(",\n", $updatedFeatures);
                $severity = FlashMessage::OK;
            } else {
                $message = 'An error occured while saving. Some settings may not have been updated.';
                $severity = FlashMessage::ERROR;
            }
        } else {
            $message = 'Nothing to update';
            $severity = FlashMessage::INFO;
        }
        return new JsonResponse([
            'success' => true,
            'status' => [
                new FlashMessage($message, 'Features updated', $severity),
            ],
        ]);
    }
}
