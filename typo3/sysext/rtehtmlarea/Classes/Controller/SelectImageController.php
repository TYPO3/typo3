<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Service\MagicImageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;

/**
 * Script class to select images in RTE
 */
class SelectImageController extends AbstractLinkBrowserController
{
    /**
     * These file extensions are allowed in the "plain" image selection mode.
     *
     * @const
     */
    const PLAIN_MODE_IMAGE_FILE_EXTENSIONS = 'jpg,jpeg,gif,png';

    /**
     * Active with TYPO3 Element Browser: Contains the name of the form field for which this window
     * opens - thus allows us to make references back to the main window in which the form is.
     * Example value: "data[pages][39][bodytext]|||tt_content|"
     * or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
     *
     * Values:
     * 0: form field name reference, eg. "data[tt_content][123][image]"
     * 1: htmlArea RTE parameters: editorNo:contentTypo3Language
     * 2: RTE config parameters: RTEtsConfigParams
     * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
     *
     * $pArr = explode('|', $this->bparams);
     * $formFieldName = $pArr[0];
     * $allowedTablesOrFileTypes = $pArr[3];
     *
     * @var string
     */
    protected $bparams;

    /**
     * RTE configuration
     *
     * @var array
     */
    protected $RTEProperties = [];

    /**
     * Used with the Rich Text Editor.
     * Example value: "tt_content:NEW3fba58c969f5c:bodytext:23:text:23:"
     *
     * @var string
     */
    protected $RTEtsConfigParams;

    /**
     * @var int
     */
    protected $editorNo;

    /**
     * TYPO3 language code of the content language
     *
     * @var int
     */
    protected $contentTypo3Language;

    /**
     * @var array
     */
    protected $buttonConfig = [];

    /**
     * Initialize controller
     */
    protected function init()
    {
        parent::init();
        $this->getLanguageService()->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_dialogs.xlf');
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function initVariables(ServerRequestInterface $request)
    {
        parent::initVariables($request);

        $queryParameters = $request->getQueryParams();
        $this->bparams = isset($queryParameters['bparams']) ? $queryParameters['bparams'] : '';
        $this->currentLinkParts['currentImage'] = !empty($queryParameters['fileUid']) ? $queryParameters['fileUid'] : 0;

        // Process bparams
        $pArr = explode('|', $this->bparams);
        $pRteArr = explode(':', $pArr[1]);
        $this->editorNo = $pRteArr[0];
        $this->contentTypo3Language = $pRteArr[1];
        $this->RTEtsConfigParams = $pArr[2];
        if (!$this->editorNo) {
            $this->editorNo = GeneralUtility::_GP('editorNo');
            $this->contentTypo3Language = GeneralUtility::_GP('contentTypo3Language');
            $this->RTEtsConfigParams = GeneralUtility::_GP('RTEtsConfigParams');
        }
        $pArr[1] = implode(':', [$this->editorNo, $this->contentTypo3Language]);
        $pArr[2] = $this->RTEtsConfigParams;
        $pArr[3] = $this->displayedLinkHandlerId === 'plain'
            ? self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS
            : '';
        $this->bparams = implode('|', $pArr);

        $RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
        $RTEsetup = $this->getBackendUser()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
        $this->RTEProperties = $RTEsetup['properties'];

        $thisConfig = BackendUtility::RTEsetup($this->RTEProperties, $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
        $this->buttonConfig = isset($thisConfig['buttons.']['image.'])
            ? $thisConfig['buttons.']['image.']
            : [];
    }

    /**
     * Initialize hook objects implementing the interface
     *
     * @throws \UnexpectedValueException
     * @return void
     */
    protected function initHookObjects()
    {
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RteImageSelector']['hooks'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RteImageSelector']['hooks'])
        ) {
            $hooks = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies(
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RteImageSelector']['hooks']
            );
            foreach ($hooks as $key => $hook) {
                $this->hookObjects[] = GeneralUtility::makeInstance($hook['handler']);
            }
        }
    }

    /**
     * Reads the configured image handlers from page TSconfig
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function getLinkHandlers()
    {
        $imageHandler = $this->buttonConfig['options.']['imageHandler.'];

        foreach ($this->hookObjects as $hookObject) {
            if (method_exists($hookObject, 'modifyImageHandlers')) {
                $imageHandler = $hookObject->modifyImageHandlers($imageHandler, $this->currentLinkParts);
            }
        }

        if (empty($imageHandler)) {
            throw new \UnexpectedValueException('No image handlers are configured. Check page TSconfig RTE.default.buttons.image.options.imageHandler.', 1455499673);
        }

        return $imageHandler;
    }

    /**
     * Initialize $this->currentLinkParts and $this->currentLinkHandler
     *
     * @return void
     */
    protected function initCurrentUrl()
    {
        if (empty($this->currentLinkParts)) {
            return;
        }

        $orderedHandlers = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($this->linkHandlers, 'scanBefore', 'scanAfter');

        // find responsible handler for current image
        foreach ($orderedHandlers as $key => $configuration) {
            /** @var LinkHandlerInterface $handler */
            $handler = $configuration['handlerInstance'];
            if ($handler->canHandleLink($this->currentLinkParts)) {
                $this->currentLinkHandler = $handler;
                $this->currentLinkHandlerId = $key;
                break;
            }
        }
        // reset the image reference if we have no handler for it
        if (!$this->currentLinkHandler) {
            $this->currentLinkParts = [];
        }
    }

    /**
     * Render the currently set URL
     *
     * @return string
     */
    protected function renderCurrentUrl()
    {
        return '<!-- Print current URL -->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-curUrl">
					<tr>
						<td>' . htmlspecialchars($this->getLanguageService()->getLL('currentImage')) . ': ' . htmlspecialchars($this->currentLinkHandler->formatCurrentUrl()) . '</td>
					</tr>
				</table>';
    }

    /**
     * Get the allowed items or tabs
     *
     * @return string[]
     */
    protected function getAllowedItems()
    {
        $allowedItems = array_keys($this->linkHandlers);

        foreach ($this->hookObjects as $hookObject) {
            if (method_exists($hookObject, 'modifyAllowedItems')) {
                $allowedItems = $hookObject->modifyAllowedItems($allowedItems, $this->currentLinkParts);
            }
        }

        return $allowedItems;
    }

    /**
     * @param array $overrides
     *
     * @return array Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $overrides = null)
    {
        return [
            'act' => isset($overrides['act']) ? $overrides['act'] : $this->displayedLinkHandlerId,
            'bparams' => $this->bparams,
            'editorNo' => $this->editorNo
        ];
    }

    /**
     * @return array
     */
    public function getButtonConfiguration()
    {
        return $this->buttonConfig;
    }

    /**
     * @return array
     */
    public function getRteProperties()
    {
        return $this->RTEProperties;
    }

    /**
     * Compile the final tags to be inserted into RTE
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function buildImageMarkup(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initVariables($request);
        $uidList = GeneralUtility::_GP('uidList');
        // handle ajax request for
        $uids = explode('|', $uidList);
        $tags = [];
        foreach ($uids as $uid) {
            $fileObject = ResourceFactory::getInstance()->getFileObject((int)$uid);
            // Get default values for alt and title attributes from file properties
            $altText = $fileObject->getProperty('alternative');
            $titleText = $fileObject->getProperty('title');
            if ($this->displayedLinkHandlerId === 'magic') {
                // Create the magic image service
                $magicImageService = GeneralUtility::makeInstance(MagicImageService::class);
                $magicImageService->setMagicImageMaximumDimensions($this->RTEProperties['default.']);
                // Create the magic image
                $imageConfiguration = [
                    'width' => GeneralUtility::_GP('cWidth'),
                    'height' => GeneralUtility::_GP('cHeight')
                ];
                $fileObject = $magicImageService->createMagicImage($fileObject, $imageConfiguration);
                $width = $fileObject->getProperty('width');
                $height = $fileObject->getProperty('height');
            } else {
                $width = $fileObject->getProperty('width');
                $height = $fileObject->getProperty('height');
                if (!$width || !$height) {
                    $filePath = $fileObject->getForLocalProcessing(false);
                    $imageInfo = @getimagesize($filePath);
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }
            $imageUrl = $fileObject->getPublicUrl();
            // If file is local, make the url absolute
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $imageUrl;
            }
            $tags[] = '<img src="' . htmlspecialchars($imageUrl) . '" width="' . htmlspecialchars($width) . '" height="' . htmlspecialchars($height) . '"'
                      . (isset($this->buttonConfig['properties.']['class.']['default'])
                    ? ' class="' . trim($this->buttonConfig['properties.']['class.']['default']) . '"'
                    : '')
                      . ' alt = "' . ($altText ? htmlspecialchars($altText) : '') . '"'
                      . ($titleText ? ' title="' . htmlspecialchars($titleText) . '"' : '')
                      . ' data-htmlarea-file-uid="' . (int)$uid . '" />';
        }
        $finalHtmlCode = implode(' ', $tags);

        $response->getBody()->write(json_encode(['images' => $finalHtmlCode]));
        return $response;
    }

    /**
     * Return the ID of current page
     *
     * @return int
     * @throws \RuntimeException
     */
    protected function getCurrentPageId()
    {
        throw new \RuntimeException('Invalid method call. This function is not supported for image handlers', 14554996791);
    }
}
