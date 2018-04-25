<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
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
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * @var int
     */
    protected $maximumCountInBadge = 99;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu');
        $this->highestSeverity = InformationStatus::cast(InformationStatus::STATUS_INFO);
    }

    /**
     * Collect the information for the menu
     */
    protected function collectInformation()
    {
        $this->getTypo3Version();
        $this->getWebServer();
        $this->getPhpVersion();
        $this->getDatabase();
        $this->getApplicationContext();
        $this->getComposerMode();
        $this->getGitRevision();
        $this->getOperatingSystem();

        $this->emitGetSystemInformation();
        $this->emitLoadMessages();

        $this->severityBadgeClass = !$this->highestSeverity->equals(InformationStatus::STATUS_NOTICE) ? 'badge-' . (string)$this->highestSeverity : '';
    }

    /**
     * Renders the menu for AJAX calls
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderMenuAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->collectInformation();
        $response->getBody()->write($this->getDropDown());
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Gets the PHP version
     */
    protected function getPhpVersion()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.phpversion',
            'value' => PHP_VERSION,
            'iconIdentifier' => 'sysinfo-php-version'
        ];
    }

    /**
     * Get the database info
     */
    protected function getDatabase()
    {
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            $this->systemInformation[] = [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.database',
                'titleAddition' => $connectionName,
                'value' => GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion(),
                'iconIdentifier' => 'sysinfo-database'
            ];
        }
    }

    /**
     * Gets the application context
     */
    protected function getApplicationContext()
    {
        $applicationContext = GeneralUtility::getApplicationContext();
        $this->systemInformation[] = [
            'title'  => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.applicationcontext',
            'value'  => (string)$applicationContext,
            'status' => $applicationContext->isProduction() ? InformationStatus::STATUS_OK : InformationStatus::STATUS_WARNING,
            'iconIdentifier' => 'sysinfo-application-context'
        ];
    }

    /**
     * Adds the information if the Composer mode is enabled or disabled to the displayed system information
     */
    protected function getComposerMode()
    {
        if (!Bootstrap::usesComposerClassLoading()) {
            return;
        }

        $this->systemInformation[] = [
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.composerMode',
            'value' => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.enabled'),
            'iconIdentifier' => 'sysinfo-composer-mode'
        ];
    }

    /**
     * Gets the current GIT revision and branch
     */
    protected function getGitRevision()
    {
        if (!StringUtility::endsWith(TYPO3_version, '-dev') || SystemEnvironmentBuilder::isFunctionDisabled('exec')) {
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
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.gitrevision',
                'value' => sprintf('%s [%s]', $revision, $branch),
                'iconIdentifier' => 'sysinfo-git'
            ];
        }
    }

    /**
     * Gets the system kernel and version
     */
    protected function getOperatingSystem()
    {
        $kernelName = PHP_OS;
        switch (strtolower($kernelName)) {
            case 'linux':
                $icon = 'linux';
                break;
            case 'darwin':
                $icon = 'apple';
                break;
            case StringUtility::beginsWith($kernelName, 'win'):
                $icon = 'windows';
                break;
            default:
                $icon = 'unknown';
        }
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.operatingsystem',
            'value' => $kernelName . ' ' . php_uname('r'),
            'iconIdentifier' => 'sysinfo-os-' . $icon
        ];
    }

    /**
     * Gets the webserver software
     */
    protected function getWebServer()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.webserver',
            'value' => $_SERVER['SERVER_SOFTWARE'],
            'iconIdentifier' => 'sysinfo-webserver'
        ];
    }

    /**
     * Gets the TYPO3 version
     */
    protected function getTypo3Version()
    {
        $this->systemInformation[] = [
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.sysinfo.typo3-version',
            'value' => VersionNumberUtility::getCurrentTypo3Version(),
            'iconIdentifier' => 'sysinfo-typo3-version'
        ];
    }

    /**
     * Emits the "getSystemInformation" signal
     */
    protected function emitGetSystemInformation()
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'getSystemInformation', [$this]);
    }

    /**
     * Emits the "loadMessages" signal
     */
    protected function emitLoadMessages()
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'loadMessages', [$this]);
    }

    /**
     * Add a system message.
     * This is a callback method for signal receivers.
     *
     * @param string $text The text to be displayed
     * @param string $status The status of this system message
     * @param int $count Will be added to the total count
     * @param string $module The associated module
     */
    public function addSystemMessage($text, $status = InformationStatus::STATUS_OK, $count = 0, $module = '')
    {
        $this->totalCount += (int)$count;

        /** @var InformationStatus $messageSeverity */
        $messageSeverity = InformationStatus::cast($status);
        // define the severity for the badge
        if ($messageSeverity->isGreaterThan($this->highestSeverity)) {
            $this->highestSeverity = $messageSeverity;
        }

        $this->systemMessages[] = [
            'module' => $module,
            'count' => (int)$count,
            'status' => $messageSeverity,
            'text' => $text
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
            'status' => $status
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

        $view = $this->getFluidTemplateObject('SystemInformationDropDown.html');
        $view->assignMultiple([
            'installToolUrl' => BackendUtility::getModuleUrl('system_extinstall'),
            'messages' => $this->systemMessages,
            'count' => $this->totalCount > $this->maximumCountInBadge ? $this->maximumCountInBadge . '+' : $this->totalCount,
            'severityBadgeClass' => $this->severityBadgeClass,
            'systemInformation' => $this->systemInformation
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
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
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

    /**
     * Get the SignalSlot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        if (!isset($this->signalSlotDispatcher)) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)
                ->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        }
        return $this->signalSlotDispatcher;
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
}
