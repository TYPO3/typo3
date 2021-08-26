<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Controller;

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

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\CoreVersion\CoreRelease;
use TYPO3\CMS\Install\ExtensionScanner\Php\CodeStatistics;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayDimensionMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayGlobalMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassNameMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstructorArgumentMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\FunctionCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\InterfaceMethodChangedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentUnusedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyExistsStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyProtectedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyPublicMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherFactory;
use TYPO3\CMS\Install\Service\ClearCacheService;
use TYPO3\CMS\Install\Service\CoreUpdateService;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Upgrade controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UpgradeController extends AbstractController
{
    /**
     * @var CoreUpdateService
     */
    protected $coreUpdateService;

    /**
     * @var CoreVersionService
     */
    protected $coreVersionService;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @param PackageManager|null $packageManager
     */
    public function __construct(PackageManager $packageManager = null)
    {
        $this->packageManager = $packageManager ?? GeneralUtility::makeInstance(PackageManager::class);
    }

    /**
     * Matcher registry of extension scanner.
     * Node visitors that implement CodeScannerInterface
     *
     * @var array
     */
    protected $matchers = [
        [
            'class' => ArrayDimensionMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayDimensionMatcher.php',
        ],
        [
            'class' => ArrayGlobalMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayGlobalMatcher.php',
        ],
        [
            'class' => ClassConstantMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassConstantMatcher.php',
        ],
        [
            'class' => ClassNameMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassNameMatcher.php',
        ],
        [
            'class' => ConstantMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ConstantMatcher.php',
        ],
        [
            'class' => ConstructorArgumentMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ConstructorArgumentMatcher.php',
        ],
        [
            'class' => PropertyAnnotationMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyAnnotationMatcher.php',
        ],
        [
            'class' => MethodAnnotationMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodAnnotationMatcher.php',
        ],
        [
            'class' => FunctionCallMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/FunctionCallMatcher.php',
        ],
        [
            'class' => InterfaceMethodChangedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/InterfaceMethodChangedMatcher.php',
        ],
        [
            'class' => MethodArgumentDroppedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedMatcher.php',
        ],
        [
            'class' => MethodArgumentDroppedStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedStaticMatcher.php',
        ],
        [
            'class' => MethodArgumentRequiredMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentRequiredMatcher.php',
        ],
        [
            'class' => MethodArgumentRequiredStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentRequiredStaticMatcher.php',
        ],
        [
            'class' => MethodArgumentUnusedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentUnusedMatcher.php',
        ],
        [
            'class' => MethodCallMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallMatcher.php',
        ],
        [
            'class' => MethodCallStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallStaticMatcher.php',
        ],
        [
            'class' => PropertyExistsStaticMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyExistsStaticMatcher.php'
        ],
        [
            'class' => PropertyProtectedMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyProtectedMatcher.php',
        ],
        [
            'class' => PropertyPublicMatcher::class,
            'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyPublicMatcher.php',
        ],
    ];

    /**
     * Main "show the cards" view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Upgrade/Cards.html');
        $view->assign('extensionFoldersInTypo3conf', (new Finder())->directories()->in(Environment::getExtensionsPath())->depth(0)->count());
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Activate a new core
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateActivateAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->activateVersion($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Check if core update is possible
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateCheckPreConditionsAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->checkPreConditions($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Download new core
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateDownloadAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->downloadVersion($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Core Update Get Data Action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Upgrade/CoreUpdate.html');
        $coreUpdateService = GeneralUtility::makeInstance(CoreUpdateService::class);
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);
        $view->assignMultiple([
            'coreUpdateEnabled' => $coreUpdateService->isCoreUpdateEnabled(),
            'coreUpdateComposerMode' => Environment::isComposerMode(),
            'coreUpdateIsReleasedVersion' => $coreVersionService->isInstalledVersionAReleasedVersion(),
            'coreUpdateIsSymLinkedCore' => is_link(Environment::getPublicPath() . '/typo3_src'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Check for new core
     *
     * @return ResponseInterface
     */
    public function coreUpdateIsUpdateAvailableAction(): ResponseInterface
    {
        $action = null;
        $this->coreUpdateInitialize();
        $messageQueue = new FlashMessageQueue('install');

        $messages = [];

        if ($this->coreVersionService->isInstalledVersionAReleasedVersion()) {
            $versionMaintenanceWindow = $this->coreVersionService->getMaintenanceWindow();
            $renderVersionInformation = false;

            if (!$versionMaintenanceWindow->isSupportedByCommunity() && !$versionMaintenanceWindow->isSupportedByElts()) {
                $messages[] = [
                    'title' => 'Outdated version',
                    'message' => 'The currently installed TYPO3 version ' . $this->coreVersionService->getInstalledVersion() . ' does not receive any further updates, please consider upgrading to a supported version!',
                    'severity' => FlashMessage::ERROR,
                ];
                $renderVersionInformation = true;
            } else {
                $currentVersion = $this->coreVersionService->getInstalledVersion();
                $isCurrentVersionElts = $this->coreVersionService->isCurrentInstalledVersionElts();
                $latestRelease = $this->coreVersionService->getYoungestPatchRelease();

                $availableReleases = [];
                if ($this->coreVersionService->isPatchReleaseSuitableForUpdate($latestRelease)) {
                    $availableReleases[] = $latestRelease;

                    if (!$latestRelease->isElts()) {
                        $action = ['title' => 'Update now to version ' . $latestRelease->getVersion(), 'action' => 'updateRegular'];
                    }
                }
                if (!$versionMaintenanceWindow->isSupportedByCommunity()) {
                    if ($latestRelease->isElts()) {
                        // Check if there's a public release left that's not installed yet
                        $latestCommunityDrivenRelease = $this->coreVersionService->getYoungestCommunityPatchRelease();
                        if ($this->coreVersionService->isPatchReleaseSuitableForUpdate($latestCommunityDrivenRelease)) {
                            $availableReleases[] = $latestCommunityDrivenRelease;
                            $action = ['title' => 'Update now to version ' . $latestCommunityDrivenRelease->getVersion(), 'action' => 'updateRegular'];
                        }
                    } elseif (!$isCurrentVersionElts) {
                        // Inform user about ELTS being available soon if:
                        // - regular support ran out
                        // - the current installed version is no ELTS
                        // - no ELTS update was released, yet
                        $messages[] = [
                            'title' => 'ELTS will be available soon',
                            'message' => sprintf('The currently installed TYPO3 version %s doesn\'t receive any community-driven updates anymore, consider subscribing to Extended Long Term Support (ELTS) releases. Please read the information below.', $currentVersion),
                            'severity' => FlashMessage::WARNING,
                        ];
                        $renderVersionInformation = true;
                    }
                }

                if ($availableReleases === []) {
                    $messages[] = [
                        'title' => 'Up to date',
                        'message' => 'There are no TYPO3 updates available.',
                        'severity' => FlashMessage::NOTICE,
                    ];
                } else {
                    foreach ($availableReleases as $availableRelease) {
                        $isUpdateSecurityRelevant = $this->coreVersionService->isUpdateSecurityRelevant($availableRelease);
                        $versionString = $availableRelease->getVersion();
                        if ($availableRelease->isElts()) {
                            $versionString .= ' ELTS';
                        }

                        if ($isUpdateSecurityRelevant) {
                            $title = ($availableRelease->isElts() ? 'ELTS ' : '') . 'Security update available!';
                            $message = sprintf('The currently installed version is %s, update to security relevant released version %s is available.', $currentVersion, $versionString);
                            $severity = FlashMessage::ERROR;
                        } else {
                            $title = ($availableRelease->isElts() ? 'ELTS ' : '') . 'Update available!';
                            $message = sprintf('Currently installed version is %s, update to regular released version %s is available.', $currentVersion, $versionString);
                            $severity = FlashMessage::WARNING;
                        }

                        if ($availableRelease->isElts()) {
                            if ($isCurrentVersionElts) {
                                $message .= ' Please visit my.typo3.org to download the release in your ELTS area.';
                            } else {
                                $message .= ' ' . sprintf('The currently installed TYPO3 version %s doesn\'t receive any community-driven updates anymore, consider subscribing to Extended Long Term Support (ELTS) releases. Please read the information below.', $currentVersion);
                            }

                            $renderVersionInformation = true;
                        }

                        $messages[] = [
                            'title' => $title,
                            'message' => $message,
                            'severity' => $severity,
                        ];
                    }
                }
            }

            if ($renderVersionInformation) {
                $supportedMajorReleases = $this->coreVersionService->getSupportedMajorReleases();
                $supportMessages = [];
                if (!empty($supportedMajorReleases['community'])) {
                    $supportMessages[] = sprintf('Currently community-supported TYPO3 versions: %s (more information at https://get.typo3.org).', implode(', ', $supportedMajorReleases['community']));
                }
                if (!empty($supportedMajorReleases['elts'])) {
                    $supportMessages[] = sprintf('Currently supported TYPO3 ELTS versions: %s (more information at https://typo3.com/elts).', implode(', ', $supportedMajorReleases['elts']));
                }

                $messages[] = [
                    'title' => 'TYPO3 Version information',
                    'message' => implode(' ', $supportMessages),
                    'severity' => FlashMessage::INFO,
                ];
            }

            foreach ($messages as $message) {
                $messageQueue->enqueue(new FlashMessage($message['message'], $message['title'], $message['severity']));
            }
        } else {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Current version is a development version and can not be updated',
                FlashMessage::WARNING
            ));
        }
        $responseData = [
            'success' => true,
            'status' => $messageQueue,
        ];
        if (isset($action)) {
            $responseData['action'] = $action;
        }
        return new JsonResponse($responseData);
    }

    /**
     * Move core to new location
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateMoveAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->moveVersion($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Unpack a downloaded core
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateUnpackAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->unpackVersion($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Verify downloaded core checksum
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function coreUpdateVerifyChecksumAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->coreUpdateInitialize();
        return new JsonResponse([
            'success' => $this->coreUpdateService->verifyFileChecksum($this->coreUpdateGetVersionToHandle($request)),
            'status' => $this->coreUpdateService->getMessages(),
        ]);
    }

    /**
     * Get list of loaded extensions
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionCompatTesterLoadedExtensionListAction(ServerRequestInterface $request): ResponseInterface
    {
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view = $this->initializeStandaloneView($request, 'Upgrade/ExtensionCompatTester.html');
        $view->assignMultiple([
            'extensionCompatTesterLoadExtLocalconfToken' => $formProtection->generateToken('installTool', 'extensionCompatTesterLoadExtLocalconf'),
            'extensionCompatTesterLoadExtTablesToken' => $formProtection->generateToken('installTool', 'extensionCompatTesterLoadExtTables'),
            'extensionCompatTesterUninstallToken' => $formProtection->generateToken('installTool', 'extensionCompatTesterUninstallExtension'),
        ]);

        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Load all ext_localconf files in order until given extension name
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionCompatTesterLoadExtLocalconfAction(ServerRequestInterface $request): ResponseInterface
    {
        $brokenExtensions = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            try {
                $this->extensionCompatTesterLoadExtLocalconfForExtension($package);
            } catch (\Throwable $e) {
                $brokenExtensions[] = [
                    'name' => $package->getPackageKey(),
                    'isProtected' => $package->isProtected()
                ];
            }
        }
        return new JsonResponse([
            'brokenExtensions' => $brokenExtensions,
        ], empty($brokenExtensions) ? 200 : 500);
    }

    /**
     * Load all ext_localconf files in order until given extension name
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionCompatTesterLoadExtTablesAction(ServerRequestInterface $request): ResponseInterface
    {
        $brokenExtensions = [];
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            // Load all ext_localconf files first
            $this->extensionCompatTesterLoadExtLocalconfForExtension($package);
        }
        foreach ($activePackages as $package) {
            try {
                $this->extensionCompatTesterLoadExtTablesForExtension($package);
            } catch (\Throwable $e) {
                $brokenExtensions[] = [
                    'name' => $package->getPackageKey(),
                    'isProtected' => $package->isProtected()
                ];
            }
        }
        return new JsonResponse([
            'brokenExtensions' => $brokenExtensions,
        ], empty($brokenExtensions) ? 200 : 500);
    }

    /**
     * Unload one extension
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function extensionCompatTesterUninstallExtensionAction(ServerRequestInterface $request): ResponseInterface
    {
        $extension = $request->getParsedBody()['install']['extension'];
        if (empty($extension)) {
            throw new \RuntimeException(
                'No extension given',
                1505407269
            );
        }
        $messageQueue = new FlashMessageQueue('install');
        if (ExtensionManagementUtility::isLoaded($extension)) {
            try {
                ExtensionManagementUtility::unloadExtension($extension);
                GeneralUtility::makeInstance(ClearCacheService::class)->clearAll();
                GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();

                $messageQueue->enqueue(new FlashMessage(
                    'Extension "' . $extension . '" unloaded.',
                    '',
                    FlashMessage::ERROR
                ));
            } catch (\Exception $e) {
                $messageQueue->enqueue(new FlashMessage(
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Create Extension Scanner Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionScannerGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $extensionsInTypo3conf = (new Finder())->directories()->in(Environment::getExtensionsPath())->depth(0)->sortByName();
        $view = $this->initializeStandaloneView($request, 'Upgrade/ExtensionScanner.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'extensionScannerExtensionList' => $extensionsInTypo3conf,
            'extensionScannerFilesToken' => $formProtection->generateToken('installTool', 'extensionScannerFiles'),
            'extensionScannerScanFileToken' => $formProtection->generateToken('installTool', 'extensionScannerScanFile'),
            'extensionScannerMarkFullyScannedRestFilesToken' => $formProtection->generateToken('installTool', 'extensionScannerMarkFullyScannedRestFiles'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Return a list of files of an extension
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionScannerFilesAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get and validate path
        $extension = $request->getParsedBody()['install']['extension'];
        $extensionBasePath = Environment::getExtensionsPath() . '/' . $extension;
        if (empty($extension) || !GeneralUtility::isAllowedAbsPath($extensionBasePath)) {
            throw new \RuntimeException(
                'Path to extension ' . $extension . ' not allowed.',
                1499777261
            );
        }
        if (!is_dir($extensionBasePath)) {
            throw new \RuntimeException(
                'Extension path ' . $extensionBasePath . ' does not exist or is no directory.',
                1499777330
            );
        }

        $finder = new Finder();
        $files = $finder->files()->in($extensionBasePath)->name('*.php')->sortByName();
        // A list of file names relative to extension directory
        $relativeFileNames = [];
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $relativeFileNames[] = GeneralUtility::fixWindowsFilePath($file->getRelativePathname());
        }
        return new JsonResponse([
            'success' => true,
            'files' => $relativeFileNames,
        ]);
    }

    /**
     * Ajax controller, part of "extension scanner". Called at the end of "scan all"
     * as last action. Gets a list of RST file hashes that matched, goes through all
     * existing RST files, finds those marked as "FullyScanned" and marks those that
     * did not had any matches as "you are not affected".
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionScannerMarkFullyScannedRestFilesAction(ServerRequestInterface $request): ResponseInterface
    {
        $foundRestFileHashes = (array)$request->getParsedBody()['install']['hashes'];
        // First un-mark files marked as scanned-ok
        $registry = new Registry();
        $registry->removeAllByNamespace('extensionScannerNotAffected');
        // Find all .rst files (except those from v8), see if they are tagged with "FullyScanned"
        // and if their content is not in incoming "hashes" array, mark as "not affected"
        $documentationFile = new DocumentationFile();
        $finder = new Finder();
        $restFilesBasePath = ExtensionManagementUtility::extPath('core') . 'Documentation/Changelog';
        $restFiles = $finder->files()->in($restFilesBasePath);
        $fullyScannedRestFilesNotAffected = [];
        foreach ($restFiles as $restFile) {
            // Skip files in "8.x" directory
            /** @var SplFileInfo $restFile */
            if (strpos($restFile->getRelativePath(), '8') === 0) {
                continue;
            }

            // Build array of file (hashes) not affected by current scan, if they are tagged as "FullyScanned"
            $parsedRestFile = array_pop($documentationFile->getListEntry(str_replace(
                '\\',
                '/',
                realpath($restFile->getPathname())
            )));
            if (!in_array($parsedRestFile['file_hash'], $foundRestFileHashes, true)
                && in_array('FullyScanned', $parsedRestFile['tags'], true)
            ) {
                $fullyScannedRestFilesNotAffected[] = $parsedRestFile['file_hash'];
            }
        }
        foreach ($fullyScannedRestFilesNotAffected as $fileHash) {
            $registry->set('extensionScannerNotAffected', $fileHash, $fileHash);
        }
        return new JsonResponse([
            'success' => true,
            'markedAsNotAffected' => count($fullyScannedRestFilesNotAffected),
        ]);
    }

    /**
     * Scan a single extension file for breaking / deprecated core code usages
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function extensionScannerScanFileAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get and validate path and file
        $extension = $request->getParsedBody()['install']['extension'];
        $extensionBasePath = Environment::getExtensionsPath() . '/' . $extension;
        if (empty($extension) || !GeneralUtility::isAllowedAbsPath($extensionBasePath)) {
            throw new \RuntimeException(
                'Path to extension ' . $extension . ' not allowed.',
                1499789246
            );
        }
        if (!is_dir($extensionBasePath)) {
            throw new \RuntimeException(
                'Extension path ' . $extensionBasePath . ' does not exist or is no directory.',
                1499789259
            );
        }
        $file = $request->getParsedBody()['install']['file'];
        $absoluteFilePath = $extensionBasePath . '/' . $file;
        if (empty($file) || !GeneralUtility::isAllowedAbsPath($absoluteFilePath)) {
            throw new \RuntimeException(
                'Path to file ' . $file . ' of extension ' . $extension . ' not allowed.',
                1499789384
            );
        }
        if (!is_file($absoluteFilePath)) {
            throw new \RuntimeException(
                'File ' . $file . ' not found or is not a file.',
                1499789433
            );
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        // Parse PHP file to AST and traverse tree calling visitors
        $statements = $parser->parse(file_get_contents($absoluteFilePath));

        // The built in NameResolver translates class names shortened with 'use' to fully qualified
        // class names at all places. Incredibly useful for us and added as first visitor.
        // IMPORTANT: first process completely to resolve fully qualified names of arguments
        // (otherwise GeneratorClassesResolver will NOT get reliable results)
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        // IMPORTANT: second process to actually work on the pre-resolved statements
        $traverser = new NodeTraverser();
        // Understand GeneralUtility::makeInstance('My\\Package\\Foo\\Bar') as fqdn class name in first argument
        $traverser->addVisitor(new GeneratorClassesResolver());
        // Count ignored lines, effective code lines, ...
        $statistics = new CodeStatistics();
        $traverser->addVisitor($statistics);

        // Add all configured matcher classes
        $matcherFactory = new MatcherFactory();
        $matchers = $matcherFactory->createAll($this->matchers);
        foreach ($matchers as $matcher) {
            $traverser->addVisitor($matcher);
        }

        $traverser->traverse($statements);

        // Gather code matches
        $matches = [[]];
        foreach ($matchers as $matcher) {
            /** @var \TYPO3\CMS\Install\ExtensionScanner\CodeScannerInterface $matcher */
            $matches[] = $matcher->getMatches();
        }
        $matches = array_merge(...$matches);

        // Prepare match output
        $restFilesBasePath = ExtensionManagementUtility::extPath('core') . 'Documentation/Changelog';
        $documentationFile = new DocumentationFile();
        $preparedMatches = [];
        foreach ($matches as $match) {
            $preparedHit = [];
            $preparedHit['uniqueId'] = str_replace('.', '', uniqid((string)mt_rand(), true));
            $preparedHit['message'] = $match['message'];
            $preparedHit['line'] = $match['line'];
            $preparedHit['indicator'] = $match['indicator'];
            $preparedHit['lineContent'] = $this->extensionScannerGetLineFromFile($absoluteFilePath, $match['line']);
            $preparedHit['restFiles'] = [];
            foreach ($match['restFiles'] as $fileName) {
                $finder = new Finder();
                $restFileLocation = $finder->files()->in($restFilesBasePath)->name($fileName);
                if ($restFileLocation->count() !== 1) {
                    throw new \RuntimeException(
                        'ResT file ' . $fileName . ' not found or multiple files found.',
                        1499803909
                    );
                }
                foreach ($restFileLocation as $restFile) {
                    /** @var SplFileInfo $restFile */
                    $restFileLocation = $restFile->getPathname();
                    break;
                }
                $parsedRestFile = array_pop($documentationFile->getListEntry(str_replace(
                    '\\',
                    '/',
                    realpath($restFileLocation)
                )));
                $version = GeneralUtility::trimExplode(DIRECTORY_SEPARATOR, $restFileLocation);
                array_pop($version);
                // something like "8.2" .. "8.7" .. "master"
                $parsedRestFile['version'] = array_pop($version);
                $parsedRestFile['uniqueId'] = str_replace('.', '', uniqid((string)mt_rand(), true));
                $preparedHit['restFiles'][] = $parsedRestFile;
            }
            $preparedMatches[] = $preparedHit;
        }
        return new JsonResponse([
            'success' => true,
            'matches' => $preparedMatches,
            'isFileIgnored' => $statistics->isFileIgnored(),
            'effectiveCodeLines' => $statistics->getNumberOfEffectiveCodeLines(),
            'ignoredLines' => $statistics->getNumberOfIgnoredLines(),
        ]);
    }

    /**
     * Check if loading ext_tables.php files still changes TCA
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function tcaExtTablesCheckAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Upgrade/TcaExtTablesCheck.html');
        $messageQueue = new FlashMessageQueue('install');
        $loadTcaService = GeneralUtility::makeInstance(LoadTcaService::class);
        $loadTcaService->loadExtensionTablesWithoutMigration();
        $baseTca = $GLOBALS['TCA'];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $this->extensionCompatTesterLoadExtLocalconfForExtension($package);

            $extensionKey = $package->getPackageKey();
            $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
            if (@file_exists($extTablesPath)) {
                $loadTcaService->loadSingleExtTablesFile($extensionKey);
                $newTca = $GLOBALS['TCA'];
                if ($newTca !== $baseTca) {
                    $messageQueue->enqueue(new FlashMessage(
                        '',
                        $extensionKey,
                        FlashMessage::NOTICE
                    ));
                }
                $baseTca = $newTca;
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
            'html' => $view->render(),
        ]);
    }

    /**
     * Check TCA for needed migrations
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function tcaMigrationsCheckAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Upgrade/TcaMigrationsCheck.html');
        $messageQueue = new FlashMessageQueue('install');
        GeneralUtility::makeInstance(LoadTcaService::class)->loadExtensionTablesWithoutMigration();
        $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        $tcaMessages = $tcaMigration->getMessages();
        foreach ($tcaMessages as $tcaMessage) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                $tcaMessage,
                FlashMessage::NOTICE
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
            'html' => $view->render(),
        ]);
    }

    /**
     * Render list of versions
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeDocsGetContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $documentationDirectories = $this->getDocumentationDirectories();
        $view = $this->initializeStandaloneView($request, 'Upgrade/UpgradeDocsGetContent.html');
        $view->assignMultiple([
            'upgradeDocsMarkReadToken' => $formProtection->generateToken('installTool', 'upgradeDocsMarkRead'),
            'upgradeDocsUnmarkReadToken' => $formProtection->generateToken('installTool', 'upgradeDocsUnmarkRead'),
            'upgradeDocsVersions' => $documentationDirectories,
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Render list of .rst files
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeDocsGetChangelogForVersionAction(ServerRequestInterface $request): ResponseInterface
    {
        $version = $request->getQueryParams()['install']['version'] ?? '';
        $this->assertValidVersion($version);

        $documentationFiles = $this->getDocumentationFiles($version);
        $view = $this->initializeStandaloneView($request, 'Upgrade/UpgradeDocsGetChangelogForVersion.html');
        $view->assignMultiple([
            'upgradeDocsFiles' => $documentationFiles['normalFiles'],
            'upgradeDocsReadFiles' => $documentationFiles['readFiles'],
            'upgradeDocsNotAffectedFiles' => $documentationFiles['notAffectedFiles'],
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Mark a .rst file as read
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeDocsMarkReadAction(ServerRequestInterface $request): ResponseInterface
    {
        $registry = new Registry();
        $filePath = $request->getParsedBody()['install']['ignoreFile'];
        $fileHash = md5_file($filePath);
        $registry->set('upgradeAnalysisIgnoredFiles', $fileHash, $filePath);
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Mark a .rst file as not read
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeDocsUnmarkReadAction(ServerRequestInterface $request): ResponseInterface
    {
        $registry = new Registry();
        $filePath = $request->getParsedBody()['install']['ignoreFile'];
        $fileHash = md5_file($filePath);
        $registry->remove('upgradeAnalysisIgnoredFiles', $fileHash);
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Check if new tables and fields should be added before executing wizards
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsBlockingDatabaseAddsAction(): ResponseInterface
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $adds = [];
        $needsUpdate = false;
        try {
            $adds = $upgradeWizardsService->getBlockingDatabaseAdds();
            if (!empty($adds)) {
                $needsUpdate = true;
            }
        } catch (StatementException $exception) {
            $needsUpdate = true;
        }
        return new JsonResponse([
            'success' => true,
            'needsUpdate' => $needsUpdate,
            'adds' => $adds,
        ]);
    }

    /**
     * Add new tables and fields
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsBlockingDatabaseExecuteAction(): ResponseInterface
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $upgradeWizardsService->addMissingTablesAndFields();
        $messages = new FlashMessageQueue('install');
        $messages->enqueue(new FlashMessage(
            '',
            'Added missing database fields and tables'
        ));
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Fix a broken DB charset setting
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsBlockingDatabaseCharsetFixAction(): ResponseInterface
    {
        $upgradeWizardsService = new UpgradeWizardsService();
        $upgradeWizardsService->setDatabaseCharsetUtf8();
        $messages = new FlashMessageQueue('install');
        $messages->enqueue(new FlashMessage(
            '',
            'Default connection database has been set to utf8'
        ));
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Test if database charset is ok
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsBlockingDatabaseCharsetTestAction(): ResponseInterface
    {
        $upgradeWizardsService = new UpgradeWizardsService();
        $result = !$upgradeWizardsService->isDatabaseCharsetUtf8();
        return new JsonResponse([
            'success' => true,
            'needsUpdate' => $result,
        ]);
    }

    /**
     * Get list of upgrade wizards marked as done
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsDoneUpgradesAction(): ResponseInterface
    {
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $wizardsDone = $upgradeWizardsService->listOfWizardsDone();
        $rowUpdatersDone = $upgradeWizardsService->listOfRowUpdatersDone();
        $messages = new FlashMessageQueue('install');
        if (empty($wizardsDone) && empty($rowUpdatersDone)) {
            $messages->enqueue(new FlashMessage(
                '',
                'No wizards are marked as done'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
            'wizardsDone' => $wizardsDone,
            'rowUpdatersDone' => $rowUpdatersDone,
        ]);
    }

    /**
     * Execute one upgrade wizard
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeWizardsExecuteAction(ServerRequestInterface $request): ResponseInterface
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $identifier = $request->getParsedBody()['install']['identifier'];
        $messages = $upgradeWizardsService->executeWizard($identifier);
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Input stage of a specific upgrade wizard
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeWizardsInputAction(ServerRequestInterface $request): ResponseInterface
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $identifier = $request->getParsedBody()['install']['identifier'];
        $result = $upgradeWizardsService->getWizardUserInput($identifier);
        return new JsonResponse([
            'success' => true,
            'status' => [],
            'userInput' => $result,
        ]);
    }

    /**
     * List available upgrade wizards
     *
     * @return ResponseInterface
     */
    public function upgradeWizardsListAction(): ResponseInterface
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();
        $upgradeWizardsService = new UpgradeWizardsService();
        $wizards = $upgradeWizardsService->getUpgradeWizardsList();
        return new JsonResponse([
            'success' => true,
            'status' => [],
            'wizards' => $wizards,
        ]);
    }

    /**
     * Mark a wizard as "not done"
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeWizardsMarkUndoneAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->loadExtLocalconfDatabaseAndExtTables();
        $wizardToBeMarkedAsUndoneIdentifier = $request->getParsedBody()['install']['identifier'];
        $upgradeWizardsService = new UpgradeWizardsService();
        $result = $upgradeWizardsService->markWizardUndone($wizardToBeMarkedAsUndoneIdentifier);
        $messages = new FlashMessageQueue('install');
        if ($result) {
            $messages->enqueue(new FlashMessage(
                'Wizard has been marked undone'
            ));
        } else {
            $messages->enqueue(new FlashMessage(
                'Wizard has not been marked undone',
                '',
                FlashMessage::ERROR
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Change install tool password
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function upgradeWizardsGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Upgrade/UpgradeWizards.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'upgradeWizardsMarkUndoneToken' => $formProtection->generateToken('installTool', 'upgradeWizardsMarkUndone'),
            'upgradeWizardsInputToken' => $formProtection->generateToken('installTool', 'upgradeWizardsInput'),
            'upgradeWizardsExecuteToken' => $formProtection->generateToken('installTool', 'upgradeWizardsExecute'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Initialize the core upgrade actions
     *
     * @throws \RuntimeException
     */
    protected function coreUpdateInitialize()
    {
        $this->coreUpdateService = GeneralUtility::makeInstance(CoreUpdateService::class);
        $this->coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);
        if (!$this->coreUpdateService->isCoreUpdateEnabled()) {
            throw new \RuntimeException(
                'Core Update disabled in this environment',
                1381609294
            );
        }
        // @todo: Does the core updater really depend on loaded ext_* files?
        $this->loadExtLocalconfDatabaseAndExtTables();
    }

    /**
     * Find out which version upgrade should be handled. This may
     * be different depending on whether development or regular release.
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @return CoreRelease
     */
    protected function coreUpdateGetVersionToHandle(ServerRequestInterface $request): CoreRelease
    {
        $type = $request->getQueryParams()['install']['type'];
        if (!isset($type) || empty($type)) {
            throw new \RuntimeException(
                'Type must be set to either "regular" or "development"',
                1380975303
            );
        }
        return $this->coreVersionService->getYoungestCommunityPatchRelease();
    }

    /**
     * Loads ext_localconf.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param PackageInterface $package
     */
    protected function extensionCompatTesterLoadExtLocalconfForExtension(PackageInterface $package)
    {
        $extLocalconfPath = $package->getPackagePath() . 'ext_localconf.php';
        // This is the main array meant to be manipulated in the ext_localconf.php files
        // In general it is recommended to not rely on it to be globally defined in that
        // scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
        // Nevertheless we define it here as global for backwards compatibility.
        global $TYPO3_CONF_VARS;
        if (@file_exists($extLocalconfPath)) {
            // $_EXTKEY and $_EXTCONF are available in ext_localconf.php
            // and are explicitly set in cached file as well
            $_EXTKEY = $package->getPackageKey();
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY] ?? null;
            require $extLocalconfPath;
        }
    }

    /**
     * Loads ext_tables.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param PackageInterface $package
     */
    protected function extensionCompatTesterLoadExtTablesForExtension(PackageInterface $package)
    {
        $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
        // In general it is recommended to not rely on it to be globally defined in that
        // scope, but we can not prohibit this without breaking backwards compatibility
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
        global $PAGES_TYPES, $TBE_STYLES;
        global $_EXTKEY;
        // Load each ext_tables.php file of loaded extensions
        $_EXTKEY = $package->getPackageKey();
        if (@file_exists($extTablesPath)) {
            // $_EXTKEY and $_EXTCONF are available in ext_tables.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY] ?? null;
            require $extTablesPath;
        }
    }

    /**
     * @return string[]
     */
    protected function getDocumentationDirectories(): array
    {
        $documentationFileService = new DocumentationFile();
        $documentationDirectories = $documentationFileService->findDocumentationDirectories(
            str_replace('\\', '/', realpath(ExtensionManagementUtility::extPath('core') . 'Documentation/Changelog'))
        );
        return array_reverse($documentationDirectories);
    }

    /**
     * Get a list of '.rst' files and their details for "Upgrade documentation" view.
     *
     * @param string $version
     * @return array
     */
    protected function getDocumentationFiles(string $version): array
    {
        $documentationFileService = new DocumentationFile();
        $documentationFiles = $documentationFileService->findDocumentationFiles(
            str_replace('\\', '/', realpath(ExtensionManagementUtility::extPath('core') . 'Documentation/Changelog/' . $version))
        );

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_registry');
        $filesMarkedAsRead = $queryBuilder
            ->select('*')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('upgradeAnalysisIgnoredFiles', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
        $hashesMarkedAsRead = [];
        foreach ($filesMarkedAsRead as $file) {
            $hashesMarkedAsRead[] = $file['entry_key'];
        }

        $fileMarkedAsNotAffected = $queryBuilder
            ->select('*')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('extensionScannerNotAffected', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
        $hashesMarkedAsNotAffected = [];
        foreach ($fileMarkedAsNotAffected as $file) {
            $hashesMarkedAsNotAffected[] = $file['entry_key'];
        }

        $readFiles = [];
        $notAffectedFiles = [];
        foreach ($documentationFiles as $fileId => $fileData) {
            if (in_array($fileData['file_hash'], $hashesMarkedAsRead, true)) {
                $readFiles[$fileId] = $fileData;
                unset($documentationFiles[$fileId]);
            } elseif (in_array($fileData['file_hash'], $hashesMarkedAsNotAffected, true)) {
                $notAffectedFiles[$fileId] = $fileData;
                unset($documentationFiles[$fileId]);
            }
        }

        return [
            'normalFiles' => $documentationFiles,
            'readFiles' => $readFiles,
            'notAffectedFiles' => $notAffectedFiles,
        ];
    }

    /**
     * Find a code line in a file
     *
     * @param string $file Absolute path to file
     * @param int $lineNumber Find this line in file
     * @return string Code line
     */
    protected function extensionScannerGetLineFromFile(string $file, int $lineNumber): string
    {
        $fileContent = file($file, FILE_IGNORE_NEW_LINES);
        $line = '';
        if (isset($fileContent[$lineNumber - 1])) {
            $line = trim($fileContent[$lineNumber - 1]);
        }
        return $line;
    }

    /**
     * Asserts that the given version is valid
     *
     * @param string $version
     * @throws \InvalidArgumentException
     */
    protected function assertValidVersion(string $version): void
    {
        if ($version !== 'master' && !preg_match('/^\d+.\d+(?:.(?:\d+|x))?$/', $version)) {
            throw new \InvalidArgumentException('Given version "' . $version . '" is invalid', 1537209128);
        }
    }
}
