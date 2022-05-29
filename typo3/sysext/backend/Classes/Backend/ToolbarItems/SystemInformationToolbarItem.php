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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render system info toolbar item
 */
class SystemInformationToolbarItem implements ToolbarItemInterface
{
    /**
     * Number displayed as badge on the dropdown trigger
     *
     * @var int
     */
    protected $totalCount = 0;

    /**
     * Holds the highest severity
     *
     * @var InformationStatus
     */
    protected $highestSeverity;

    /**
     * The CSS class for the badge
     *
     * @var string
     */
    protected $severityBadgeClass = '';

    /**
     * @var array
     */
    protected $systemInformation = [];

    /**
     * @var array
     */
    protected $systemMessages = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var int
     */
    protected $maximumCountInBadge = 99;

    /**
     * @var Typo3Version
     */
    protected $typo3Version;

    public function __construct(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?? GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu');
        $this->highestSeverity = InformationStatus::cast(InformationStatus::STATUS_INFO);
    }

    /**
     * Add a system message.
     * This is a callback method for signal receivers.
     *
     * @param string $text The text to be displayed
     * @param string $status The status of this system message
     * @param int $count Will be added to the total count
     * @param string $module The associated module
     * @param string $params Query string with additional parameters
     */
    public function addSystemMessage($text, $status = InformationStatus::STATUS_OK, $count = 0, $module = '', $params = '')
    {
        $this->totalCount += (int)$count;

        $messageSeverity = InformationStatus::cast($status);
        // define the severity for the badge
        if ($messageSeverity->isGreaterThan($this->highestSeverity)) {
            $this->highestSeverity = $messageSeverity;
        }

        $this->systemMessages[] = [
            'module' => $module,
            'params' => $params,
            'count' => (int)$count,
            'status' => $messageSeverity,
            'text' => $text,
        ];
    }

    /**
     * Add a system information.
     * This is a callback method for signal receivers.
     *
     * @param string $title The title of this system information
     * @param string $value The associated value
     * @param string $iconIdentifier The icon identifier
     * @param string $status The status of this system information
     */
    public function addSystemInformation($title, $value, $iconIdentifier, $status = InformationStatus::STATUS_NOTICE)
    {
        $this->systemInformation[] = [
            'title' => $title,
            'value' => $value,
            'iconIdentifier' => $iconIdentifier,
            'status' => $status,
        ];
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        return $this->getBackendUserAuthentication()->isAdmin();
    }

    /**
     * Render system information dropdown
     *
     * @return string Icon HTML
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('SystemInformationToolbarItem.html')->render();
    }

    /**
     * Render drop down
     *
     * @return string Drop down HTML
     */
    public function getDropDown()
    {
        if (!$this->checkAccess()) {
            return '';
        }

        $this->collectInformation();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $view = $this->getFluidTemplateObject('SystemInformationDropDown.html');

        try {
            $environmentToolUrl = (string)$uriBuilder->buildUriFromRoute('tools_toolsenvironment');
        } catch (RouteNotFoundException $e) {
            $environmentToolUrl = '';
        }

        $view->assignMultiple([
            'environmentToolUrl' => $environmentToolUrl,
            'messages' => $this->systemMessages,
            'count' => $this->totalCount > $this->maximumCountInBadge ? $this->maximumCountInBadge . '+' : $this->totalCount,
            'severityBadgeClass' => $this->severityBadgeClass,
            'systemInformation' => $this->systemInformation,
        ]);
        return $view->render();
    }

    /**
     * No additional attributes needed.
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 75;
    }

    /**
     * Collect the information for the menu
     */
    protected function collectInformation()
    {
        $this->getTypo3Version();
        $this->getWebServer();
        $this->getPhpVersion();
        $this->getDebugger();
        $this->getDatabase();
        $this->getApplicationContext();
        $this->getComposerMode();
        $this->getGitRevision();
        $this->getOperatingSystem();

        $this->eventDispatcher->dispatch(new SystemInformationToolbarCollectorEvent($this));

        $this->severityBadgeClass = !$this->highestSeverity->equals(InformationStatus::STATUS_NOTICE) ? 'badge-' . (string)$this->highestSeverity : '';
    }

    /**
     * Gets the TYPO3 version
     */
    protected function getTypo3Version()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.typo3-version',
            'value' => $this->typo3Version->getVersion(),
            'iconIdentifier' => 'information-typo3-version',
        ];
    }

    /**
     * Gets the webserver software
     */
    protected function getWebServer()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.webserver',
            'value' => $_SERVER['SERVER_SOFTWARE'],
            'iconIdentifier' => 'information-webserver',
        ];
    }

    /**
     * Gets the PHP version
     */
    protected function getPhpVersion()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.phpversion',
            'value' => PHP_VERSION,
            'iconIdentifier' => 'information-php-version',
        ];
    }

    protected function getDebugger(): void
    {
        $knownDebuggers = ['xdebug', 'Zend Debugger'];
        foreach ($knownDebuggers as $debugger) {
            if (extension_loaded($debugger)) {
                $debuggerVersion = phpversion($debugger) ?: '';
                $this->systemInformation[] = [
                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.debugger',
                    'value' => sprintf('%s %s', $debugger, $debuggerVersion),
                    'iconIdentifier' => 'information-debugger',
                ];
            }
        }
    }

    /**
     * Get the database info
     */
    protected function getDatabase()
    {
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            $serverVersion = '[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.database.offline') . ']';
            $success = true;
            try {
                $serverVersion = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (\Exception $exception) {
                $success = false;
            }
            $this->systemInformation[] = [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.database',
                'titleAddition' => $connectionName,
                'value' => $serverVersion,
                'status' => $success ?: InformationStatus::STATUS_WARNING,
                'iconIdentifier' => 'information-database',
            ];
        }
    }

    /**
     * Gets the application context
     */
    protected function getApplicationContext()
    {
        $applicationContext = Environment::getContext();
        $this->systemInformation[] = [
            'title'  => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.applicationcontext',
            'value'  => (string)$applicationContext,
            'status' => $applicationContext->isProduction() ? InformationStatus::STATUS_OK : InformationStatus::STATUS_WARNING,
            'iconIdentifier' => 'information-application-context',
        ];
    }

    /**
     * Adds the information if the Composer mode is enabled or disabled to the displayed system information
     */
    protected function getComposerMode()
    {
        if (!Environment::isComposerMode()) {
            return;
        }

        $this->systemInformation[] = [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.composerMode',
            'value' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled'),
            'iconIdentifier' => 'information-composer-mode',
        ];
    }

    /**
     * Gets the current GIT revision and branch
     */
    protected function getGitRevision()
    {
        if (!str_ends_with($this->typo3Version->getVersion(), '-dev') || $this->isFunctionDisabled('exec')) {
            return;
        }
        // check if git exists
        CommandUtility::exec('git --version', $_, $returnCode);
        if ((int)$returnCode !== 0) {
            // git is not available
            return;
        }

        $revision = trim(CommandUtility::exec('git rev-parse --short HEAD'));
        $branch = trim(CommandUtility::exec('git rev-parse --abbrev-ref HEAD'));
        if (!empty($revision) && !empty($branch)) {
            $this->systemInformation[] = [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.gitrevision',
                'value' => sprintf('%s [%s]', $revision, $branch),
                'iconIdentifier' => 'information-git',
            ];
        }
    }

    /**
     * Gets the system kernel and version
     */
    protected function getOperatingSystem()
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
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.operatingsystem',
            'value' => PHP_OS . ' ' . php_uname('r'),
            'iconIdentifier' => 'information-os-' . $icon,
        ];
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials/ToolbarItems']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ToolbarItems']);

        $view->setTemplate($filename);

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    /**
     * Check if the given PHP function is disabled in the system
     *
     * @param string $functionName
     * @return bool
     */
    protected function isFunctionDisabled(string $functionName): bool
    {
        $disabledFunctions = GeneralUtility::trimExplode(',', (string)ini_get('disable_functions'));
        if (!empty($disabledFunctions)) {
            return in_array($functionName, $disabledFunctions, true);
        }
        return false;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
