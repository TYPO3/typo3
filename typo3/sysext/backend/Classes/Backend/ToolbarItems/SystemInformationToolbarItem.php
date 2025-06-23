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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus as DeprecatedInformationStatus;
use TYPO3\CMS\Backend\Toolbar\InformationStatus;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render system information toolbar item and drop-down.
 * Provides some events for other extensions to add information.
 */
#[Autoconfigure(public: true)]
class SystemInformationToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;
    protected array $systemInformation = [];
    protected InformationStatus $highestSeverity;
    protected string $severityBadgeClass = '';
    protected array $systemMessages = [];
    protected int $systemMessageTotalCount = 0;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Typo3Version $typo3Version,
        private readonly BackendViewFactory $backendViewFactory,
    ) {
        $this->highestSeverity = InformationStatus::INFO;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Add a system message.
     * This is a callback method for signal receivers.
     *
     * @param string $text The text to be displayed
     * @param string|InformationStatus $status The status of this system message
     * @param int $count Will be added to the total count
     * @param string $module The associated module
     * @param string $params Query string with additional parameters
     */
    public function addSystemMessage($text, $status = InformationStatus::OK, $count = 0, $module = '', $params = ''): void
    {
        $this->systemMessageTotalCount += $count;

        if (is_string($status)) {
            trigger_error(
                'Using a string of the non-native enumeration ' . DeprecatedInformationStatus::class . ' for $status '
                . 'in SystemInformationToolbarItem->addSystemMessage() has been deprecated and will stop working in '
                . 'TYPO3 v14.0. Use the native ' . InformationStatus::class . ' instead.',
                E_USER_DEPRECATED
            );
            $messageSeverity = InformationStatus::tryFrom($status) ?? InformationStatus::OK;
        } elseif ($status instanceof InformationStatus) {
            $messageSeverity = $status;
        } else {
            $messageSeverity = InformationStatus::OK;
        }

        // define the severity for the badge
        if ($messageSeverity->isGreaterThan($this->highestSeverity)) {
            $this->highestSeverity = $messageSeverity;
        }

        $this->systemMessages[] = [
            'module' => $module,
            'params' => $params,
            'count' => $count,
            'status' => $messageSeverity->value,
            'text' => $text,
        ];
    }

    /**
     * Add a system information.
     * This is a callback method for signal receivers.
     *
     * @param string $title The title of this system information, typically a LLL:EXT:... label string
     * @param string $value The associated value
     * @param string $iconIdentifier The icon identifier
     * @param string|InformationStatus $status The status of this system information
     */
    public function addSystemInformation($title, $value, $iconIdentifier, $status = InformationStatus::NOTICE): void
    {
        if (is_string($status)) {
            trigger_error(
                'Using a string of the non-native enumeration ' . DeprecatedInformationStatus::class . ' for $status '
                . 'in SystemInformationToolbarItem->addSystemInformation() has been deprecated and will stop working in '
                . 'TYPO3 v14.0. Use the native ' . InformationStatus::class . ' instead.',
                E_USER_DEPRECATED
            );
            $status = InformationStatus::tryFrom($status) ?? InformationStatus::NOTICE;
        } elseif (!$status instanceof InformationStatus) {
            $status = InformationStatus::OK;
        }

        $this->systemInformation[] = [
            'title' => $title,
            'value' => $value,
            'iconIdentifier' => $iconIdentifier,
            'status' => $status->value,
        ];
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        return $this->getBackendUserAuthentication()->isAdmin();
    }

    /**
     * Render system information dropdown.
     */
    public function getItem(): string
    {
        $view = $this->backendViewFactory->create($this->request);
        return $view->render('ToolbarItems/SystemInformationToolbarItem');
    }

    /**
     * Render drop-down
     */
    public function getDropDown(): string
    {
        if (!$this->checkAccess()) {
            return '';
        }
        $this->collectInformation();
        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'messages' => $this->systemMessages,
            'count' => $this->systemMessageTotalCount > 99 ? '99+' : $this->systemMessageTotalCount,
            'severityBadgeClass' => $this->severityBadgeClass,
            'systemInformation' => $this->systemInformation,
        ]);
        return $view->render('ToolbarItems/SystemInformationDropDown');
    }

    /**
     * No additional attributes needed.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 75;
    }

    /**
     * Collect the information for the drop-down.
     */
    protected function collectInformation(): void
    {
        $this->addTypo3Version();
        $this->addInstallationMode();
        $this->addWebServer();
        $this->addPhpVersion();
        $this->addDebugger();
        $this->addDatabase();
        $this->addApplicationContext();
        $this->addGitRevision();
        $this->addOperatingSystem();
        $this->eventDispatcher->dispatch(new SystemInformationToolbarCollectorEvent($this));
        $this->severityBadgeClass = $this->highestSeverity !== InformationStatus::NOTICE ? 'badge-' . $this->highestSeverity->value : '';
    }

    protected function addTypo3Version(): void
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.typo3-version',
            'value' => $this->typo3Version->getVersion(),
            'iconIdentifier' => 'information-typo3-version',
        ];
    }

    protected function addInstallationMode(): void
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.installationMethod',
            'value' => Environment::isComposerMode() ?
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.installationMethod.composer') :
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.installationMethod.classic'),
            'iconIdentifier' => 'actions-package',
        ];
    }

    protected function addWebServer(): void
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.webserver',
            'value' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'iconIdentifier' => 'information-webserver',
        ];
    }

    protected function addPhpVersion(): void
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.phpversion',
            'value' => PHP_VERSION,
            'iconIdentifier' => 'information-php-version',
        ];
    }

    protected function addDebugger(): void
    {
        $knownDebuggers = ['xdebug', 'Zend Debugger'];
        foreach ($knownDebuggers as $debugger) {
            if (extension_loaded($debugger)) {
                $debuggerVersion = phpversion($debugger) ?: '';
                $this->systemInformation[] = [
                    'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.debugger',
                    'value' => sprintf('%s %s', $debugger, $debuggerVersion),
                    'iconIdentifier' => 'information-debugger',
                ];
            }
        }
    }

    protected function addDatabase(): void
    {
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            $serverVersion = '[' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.database.offline') . ']';
            $success = true;
            try {
                $serverVersion = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getPlatformServerVersion();
            } catch (\Exception $exception) {
                $success = false;
            }
            $this->systemInformation[] = [
                'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.database',
                'titleAddition' => $connectionName,
                'value' => $serverVersion,
                'status' => $success ?: InformationStatus::WARNING->value,
                'iconIdentifier' => 'information-database',
            ];
        }
    }

    protected function addApplicationContext(): void
    {
        $applicationContext = Environment::getContext();
        $this->systemInformation[] = [
            'title'  => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.applicationcontext',
            'value'  => (string)$applicationContext,
            'status' => $applicationContext->isProduction() ? InformationStatus::OK->value : InformationStatus::WARNING->value,
            'iconIdentifier' => 'information-application-context',
        ];
    }

    /**
     * Gets the current GIT revision and branch
     */
    protected function addGitRevision(): void
    {
        if (!str_ends_with($this->typo3Version->getVersion(), '-dev') || $this->isFunctionDisabled('exec')) {
            return;
        }
        // check if git exists
        $returnCode = 0;
        CommandUtility::exec('git --version', $_, $returnCode);
        if ($returnCode !== 0) {
            // git is not available
            return;
        }

        $revision = trim(CommandUtility::exec('git rev-parse --short HEAD'));
        $branch = trim(CommandUtility::exec('git rev-parse --abbrev-ref HEAD'));
        if (!empty($revision) && !empty($branch)) {
            $this->systemInformation[] = [
                'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.gitrevision',
                'value' => sprintf('%s [%s]', $revision, $branch),
                'iconIdentifier' => 'information-git',
            ];
        }
    }

    /**
     * Gets the system kernel and version
     */
    protected function addOperatingSystem(): void
    {
        switch (PHP_OS_FAMILY) {
            case 'Linux':
                $icon = 'linux';
                break;
            case 'Darwin':
                $icon = 'apple';
                break;
            case 'Windows':
                $icon = 'windows';
                break;
            default:
                $icon = 'unknown';
        }
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.sysinfo.operatingsystem',
            'value' => PHP_OS . ' ' . php_uname('r'),
            'iconIdentifier' => 'information-os-' . $icon,
        ];
    }

    /**
     * Check if the given PHP function is disabled in the system.
     */
    protected function isFunctionDisabled(string $functionName): bool
    {
        $disabledFunctions = GeneralUtility::trimExplode(',', (string)ini_get('disable_functions'));
        if (!empty($disabledFunctions)) {
            return in_array($functionName, $disabledFunctions, true);
        }
        return false;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
