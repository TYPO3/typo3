<?php
namespace TYPO3\CMS\Recordlist\Controller;

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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface;

/**
 * Script class for the Element Browser window.
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ElementBrowserController
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'main' => 'Using ElementBrowserController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * The mode determines the main kind of output of the element browser.
     *
     * There are these options for values:
     *  - "db" will allow you to browse for pages or records in the page tree for FormEngine select fields
     *  - "file" will allow you to browse for files in the folder mounts for FormEngine file selections
     *  - "folder" will allow you to browse for folders in the folder mounts for FormEngine folder selections
     *  - Other options may be registered via extensions
     *
     * @var string
     */
    protected $mode;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;

        // Creating backend template object:
        // this might not be needed but some classes refer to $GLOBALS['SOBE']->doc, so ...
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);

        $this->init();
    }

    /**
     * Initialize the controller
     */
    protected function init()
    {
        $this->getLanguageService()->includeLLFile('EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf');

        $this->mode = GeneralUtility::_GP('mode');
    }

    /**
     * Injects the request object for the current request or sub-request
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // Fallback for old calls, which use mode "wizard" or "rte" for link selection
        if ($this->mode === 'wizard' || $this->mode === 'rte') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            return new RedirectResponse((string)$uriBuilder->buildUriFromRoute('wizard_link', $_GET), 303);
        }
        return new HtmlResponse($this->main());
    }

    /**
     * Main function, detecting the current mode of the element browser and branching out to internal methods.
     *
     * @return string HTML content
     */
    protected function main()
    {
        $content = '';

        // Render type by user func
        $browserRendered = false;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'] ?? [] as $className) {
            $browserRenderObj = GeneralUtility::makeInstance($className);
            if (is_object($browserRenderObj) && method_exists($browserRenderObj, 'isValid') && method_exists($browserRenderObj, 'render')) {
                if ($browserRenderObj->isValid($this->mode, $this)) {
                    $content = $browserRenderObj->render($this->mode, $this);
                    $browserRendered = true;
                    break;
                }
            }
        }

        // if type was not rendered use default rendering functions
        if (!$browserRendered) {
            $browser = $this->getElementBrowserInstance();

            $backendUser = $this->getBackendUser();
            $modData = $backendUser->getModuleData('browse_links.php', 'ses');
            list($modData) = $browser->processSessionData($modData);
            $backendUser->pushModuleData('browse_links.php', $modData);

            $content = $browser->render();
        }

        return $content;
    }

    /**
     * Get instance of the actual element browser
     *
     * This method shall be overwritten in subclasses
     *
     * @return ElementBrowserInterface
     * @throws \UnexpectedValueException
     */
    protected function getElementBrowserInstance()
    {
        $className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers'][$this->mode];
        $browser = GeneralUtility::makeInstance($className);
        if (!$browser instanceof ElementBrowserInterface) {
            throw new \UnexpectedValueException('The specified element browser "' . $className . '" does not implement the required ElementBrowserInterface', 1442763890);
        }
        return $browser;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
