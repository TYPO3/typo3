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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render system info toolbar item
 */
class SystemInformationToolbarItem implements ToolbarItemInterface
{
    /**
     * @var StandaloneView
     */
    protected $standaloneView = null;

    /**
     * Template file for the dropdown menu
     */
    const TOOLBAR_MENU_TEMPLATE = 'SystemInformation.html';

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
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int
     */
    protected $maximumCountInBadge = 99;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!$this->checkAccess()) {
            return;
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $extPath = ExtensionManagementUtility::extPath('backend');
        /* @var $view StandaloneView */
        $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->standaloneView->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/ToolbarMenu/' . static::TOOLBAR_MENU_TEMPLATE);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu');

        $this->highestSeverity = InformationStatus::cast(InformationStatus::STATUS_INFO);
    }

    /**
     * Collect the information for the menu
     */
    protected function collectInformation()
    {
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
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Gets the PHP version
     *
     * @return void
     */
    protected function getPhpVersion()
    {
        $this->systemInformation[] = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.phpversion', true),
            'value' => PHP_VERSION,
            'icon' => $this->iconFactory->getIcon('sysinfo-php-version', Icon::SIZE_SMALL)->render()
        ];
    }

    /**
     * Get the database info
     *
     * @return void
     */
    protected function getDatabase()
    {
        $this->systemInformation[] = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.database', true),
            'value' => $this->getDatabaseConnection()->getServerVersion(),
            'icon' => $this->iconFactory->getIcon('sysinfo-database', Icon::SIZE_SMALL)->render()
        ];
    }

    /**
     * Gets the application context
     *
     * @return void
     */
    protected function getApplicationContext()
    {
        $applicationContext = GeneralUtility::getApplicationContext();
        $this->systemInformation[] = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.applicationcontext', true),
            'value' => (string)$applicationContext,
            'status' => $applicationContext->isProduction() ? InformationStatus::STATUS_OK : InformationStatus::STATUS_WARNING,
            'icon' => $this->iconFactory->getIcon('sysinfo-application-context', Icon::SIZE_SMALL)->render()
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

        $languageService = $this->getLanguageService();
        $this->systemInformation[] = [
            'title' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.composerMode', true),
            'value' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.enabled', true),
            'icon' => $this->iconFactory->getIcon('sysinfo-composer-mode', Icon::SIZE_SMALL)->render()
        ];
    }

    /**
     * Gets the current GIT revision and branch
     *
     * @return void
     */
    protected function getGitRevision()
    {
        if (!StringUtility::endsWith(TYPO3_version, '-dev') || \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::isFunctionDisabled('exec')) {
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
                'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.gitrevision', true),
                'value' => sprintf('%s [%s]', $revision, $branch),
                'icon' => $this->iconFactory->getIcon('sysinfo-git', Icon::SIZE_SMALL)->render()
            ];
        }
    }

    /**
     * Gets the system kernel and version
     *
     * @return void
     */
    protected function getOperatingSystem()
    {
        $kernelName = php_uname('s');
        switch (strtolower($kernelName)) {
            case 'linux':
                $icon = 'linux';
                break;
            case 'darwin':
                $icon = 'apple';
                break;
            default:
                $icon = 'windows';
        }
        $this->systemInformation[] = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.operatingsystem', true),
            'value' => $kernelName . ' ' . php_uname('r'),
            'icon' => $this->iconFactory->getIcon('sysinfo-os-' . $icon, Icon::SIZE_SMALL)->render()
        ];
    }

    /**
     * Gets the webserver software
     */
    protected function getWebServer()
    {
        $this->systemInformation[] = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo.webserver', true),
            'value' => htmlspecialchars($_SERVER['SERVER_SOFTWARE']),
            'icon' => $this->iconFactory->getIcon('sysinfo-webserver', Icon::SIZE_SMALL)->render()
        ];
    }

    /**
     * Emits the "getSystemInformation" signal
     *
     * @return void
     */
    protected function emitGetSystemInformation()
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'getSystemInformation', [$this]);
    }

    /**
     * Emits the "loadMessages" signal
     *
     * @return void
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
     * @param string $icon The icon html
     */
    public function addSystemInformation($title, $value, $icon)
    {
        $this->systemInformation[] = [
            'title' => $title,
            'value' => $value,
            'icon' => $icon
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
        $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.sysinfo', true);
        $icon = $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render('inline');
        return '<span title="' . $title . '">' . $icon . '<span id="t3js-systeminformation-counter" class="badge"></span></span>';
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

        $request = $this->standaloneView->getRequest();
        $request->setControllerExtensionName('backend');
        $this->standaloneView->assignMultiple([
            'installToolUrl' => BackendUtility::getModuleUrl('system_InstallInstall'),
            'messages' => $this->systemMessages,
            'count' => $this->totalCount > $this->maximumCountInBadge ? $this->maximumCountInBadge . '+' : $this->totalCount,
            'severityBadgeClass' => $this->severityBadgeClass,
            'systemInformation' => $this->systemInformation
        ]);
        return $this->standaloneView->render();
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
     * Returns DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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
}
