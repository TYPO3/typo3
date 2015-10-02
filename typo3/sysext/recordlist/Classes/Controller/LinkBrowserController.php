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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;

/**
 * Script class for the Link Browser window.
 */
class LinkBrowserController {

	/**
	 * @var DocumentTemplate
	 */
	protected $doc;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * URL of current request
	 *
	 * @var string
	 */
	protected $thisScript = '';

	/**
	 * @var LinkHandlerInterface[]
	 */
	protected $linkHandlers = [];

	/**
	 * @var string
	 */
	protected $currentLink = '';

	/**
	 * Link handler responsible for the current active link
	 *
	 * @var LinkHandlerInterface $currentLinkHandler
	 */
	protected $currentLinkHandler;

	/**
	 * The ID of the currently active link handler
	 *
	 * @var string
	 */
	protected $currentLinkHandlerId;

	/**
	 * Link handler to be displayed
	 *
	 * @var LinkHandlerInterface $displayedLinkHandler
	 */
	protected $displayedLinkHandler;

	/**
	 * The ID of the displayed link handler
	 *
	 * This is read from the 'act' GET parameter
	 *
	 * @var string
	 */
	protected $displayedLinkHandlerId = '';

	/**
	 * List of available link attribute fields
	 *
	 * @var string[]
	 */
	protected $linkAttributeFields = [];

	/**
	 * Values of the link attributes
	 *
	 * @var string[]
	 */
	protected $linkAttributeValues = [];

	/**
	 * @var array
	 */
	protected $hookObjects = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initHookObjects();
		$this->init();
	}

	/**
	 * Initialize the controller
	 *
	 * @return void
	 */
	protected function init() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_browse_links.xlf');
	}

	/**
	 * Initialize hook objects implementing the interface
	 *
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	protected function initHookObjects() {
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks'])
			&& is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks'])
		) {
			$hooks = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies(
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks']
			);
			foreach ($hooks as $key => $hook) {
				$this->hookObjects[] = GeneralUtility::makeInstance($hook['handler']);
			}
		}
	}

	/**
	 * Injects the request object for the current request or subrequest
	 * As this controller goes only through the main() method, it is rather simple for now
	 *
	 * @param ServerRequestInterface $request the current request
	 * @param ResponseInterface $response the prepared response object
	 * @return ResponseInterface the response with the content
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$this->determineScriptUrl($request);
		$this->initVariables($request);
		$this->loadLinkHandlers();
		$this->initCurrentUrl();

		$menuData = $this->buildMenuArray();
		$renderLinkAttributeFields = $this->renderLinkAttributeFields();
		$browserContent = $this->displayedLinkHandler->render($request);

		$this->initDocumentTemplate();
		$content = $this->doc->startPage('Link Browser');
		$content .= $this->doc->getFlashMessages();

		if ($this->currentLink) {
			$content .= '<!-- Print current URL -->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-curUrl">
					<tr>
						<td>' . $this->getLanguageService()->getLL('currentLink', TRUE) . ': ' . htmlspecialchars($this->currentLinkHandler->formatCurrentUrl()) . '</td>
					</tr>
				</table>';
		}
		$content .= $this->doc->getTabMenuRaw($menuData);
		$content .= $renderLinkAttributeFields;

		$content .= '<div class="linkBrowser-tabContent">' . $browserContent . '</div>';
		$content .= $this->doc->endPage();

		$response->getBody()->write($this->doc->insertStylesAndJS($content));
		return $response;
	}

	/**
	 * Sets the script url depending on being a module or script request
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @throws \TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException
	 * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
	 */
	protected function determineScriptUrl(ServerRequestInterface $request) {
		if ($routePath = $request->getQueryParams()['route']) {
			$router = GeneralUtility::makeInstance(Router::class);
			$route = $router->match($routePath);
			$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
			$this->thisScript = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'));
		} elseif ($moduleName = $request->getQueryParams()['M']) {
			$this->thisScript = BackendUtility::getModuleUrl($moduleName);
		} else {
			$this->thisScript = GeneralUtility::getIndpEnv('SCRIPT_NAME');
		}
	}

	/**
	 * @param ServerRequestInterface $request
	 */
	protected function initVariables(ServerRequestInterface $request) {
		$queryParams = $request->getQueryParams();
		$act = isset($queryParams['act']) ? $queryParams['act'] : '';
		// @deprecated since CMS 7, remove with CMS 8
		if (strpos($act, '|')) {
			GeneralUtility::deprecationLog('Using multiple values for the "act" parameter in the link wizard is deprecated. Only a single value is allowed. Values were: ' . $act);
			$act = array_shift(explode('|', $act));
		}
		$this->displayedLinkHandlerId = $act;
		$this->parameters = isset($queryParams['P']) ? $queryParams['P'] : [];
		$this->linkAttributeValues = isset($queryParams['linkAttributes']) ? $queryParams['linkAttributes'] : [];
		$this->currentLink = isset($this->parameters['currentValue']) ? trim($this->parameters['currentValue']) : '';
	}

	/**
	 * @return void
	 * @throws \UnexpectedValueException
	 */
	protected function loadLinkHandlers() {
		$linkHandlers = $this->getLinkHandlers();
		if (empty($linkHandlers)) {
			throw new \UnexpectedValueException('No link handlers are configured. Check page TSconfig TCEMAIN.linkHandlers.', 1442787911);
		}

		$lang = $this->getLanguageService();
		foreach ($linkHandlers as $identifier => $configuration) {
			$identifier = rtrim($identifier, '.');
			/** @var LinkHandlerInterface $handler */
			$handler = GeneralUtility::makeInstance($configuration['handler']);
			$handler->initialize(
				$this,
				$identifier,
				isset($configuration['configuration.']) ? $configuration['configuration.'] : []
			);

			$this->linkHandlers[$identifier] = [
				'handlerInstance' => $handler,
				'label' => $lang->sL($configuration['label'], TRUE),
				'displayBefore' => isset($configuration['displayBefore']) ? GeneralUtility::trimExplode(',', $configuration['displayBefore']) : [],
				'displayAfter' => isset($configuration['displayAfter']) ? GeneralUtility::trimExplode(',', $configuration['displayAfter']) : [],
				'scanBefore' => isset($configuration['scanBefore']) ? GeneralUtility::trimExplode(',', $configuration['scanBefore']) : [],
				'scanAfter' => isset($configuration['scanAfter']) ? GeneralUtility::trimExplode(',', $configuration['scanAfter']) : [],
				'addParams' => isset($configuration['addParams']) ? $configuration['addParams'] : '',
			];
		}
	}

	/**
	 * Reads the configured link handlers from page TSconfig
	 *
	 * @return array
	 */
	protected function getLinkHandlers() {
		$pageTSconfig = BackendUtility::getPagesTSconfig($this->getCurrentPageId());
		$pageTSconfig = $this->getBackendUser()->getTSConfig('TCEMAIN.linkHandler.', $pageTSconfig);
		$linkHandlers = (array)$pageTSconfig['properties'];

		foreach ($this->hookObjects as $hookObject) {
			if (method_exists($hookObject, 'modifyLinkHandlers')) {
				$linkHandlers = $hookObject->modifyLinkHandlers($linkHandlers, $this->currentLink);
			}
		}

		return $linkHandlers;
	}

	/**
	 * Initialize $this->currentLink and $this->currentLinkHandler
	 *
	 * @return void
	 */
	protected function initCurrentUrl() {
		if (!$this->currentLink) {
			return;
		}

		$currentLinkParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($this->currentLink);
		$currentLinkParts['params'] = $currentLinkParts['additionalParams'];
		unset($currentLinkParts['additionalParams']);

		$orderedHandlers = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($this->linkHandlers, 'scanBefore', 'scanAfter');

		// find responsible handler for current link
		foreach ($orderedHandlers as $key => $configuration) {
			/** @var LinkHandlerInterface $handler */
			$handler = $configuration['handlerInstance'];
			if ($handler->canHandleLink($currentLinkParts)) {
				$this->currentLinkHandler = $handler;
				$this->currentLinkHandlerId = $key;
				break;
			}
		}
		// reset the link if we have no handler for it
		if (!$this->currentLinkHandler) {
			$this->currentLink = '';
		}

		unset($currentLinkParts['url']);
		// overwrite any preexisting
		foreach ($currentLinkParts as $key => $part) {
			$this->linkAttributeValues[$key] = $part;
		}
	}

	/**
	 * Initialize document template object
	 *
	 *  @return void
	 */
	protected function initDocumentTemplate() {
		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$this->doc->bodyTagId = 'typo3-browse-links-php';

		if (!$this->areFieldChangeFunctionsValid() && !$this->areFieldChangeFunctionsValid(TRUE)) {
			$this->parameters['fieldChangeFunc'] = array();
		}
		unset($this->parameters['fieldChangeFunc']['alert']);
		$update = [];
		foreach ($this->parameters['fieldChangeFunc'] as $v) {
			$update[] = 'parent.opener.' . $v;
		}
		$inlineJS = implode(LF, $update);

		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->loadJquery();
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/LinkBrowser', 'function(LinkBrowser) {
			LinkBrowser.updateFunctions = function() {' . $inlineJS . '};
		}');

		foreach ($this->getBodyTagAttributes() as $attributeName => $value) {
			$this->doc->bodyTagAdditions .= ' ' . $attributeName . '="' . htmlspecialchars($value) . '"';
		}

		// Finally, add the accumulated JavaScript to the template object:
		// also unset the default jumpToUrl() function before
		unset($this->doc->JScodeArray['jumpToUrl']);
	}

	/**
	 * Returns an array definition of the top menu
	 *
	 * @return mixed[][]
	 */
	protected function buildMenuArray() {
		$allowedItems = $this->getAllowedItems();
		if ($this->displayedLinkHandlerId && !in_array($this->displayedLinkHandlerId, $allowedItems, TRUE)) {
			$this->displayedLinkHandlerId = '';
		}

		$allowedHandlers = array_flip($allowedItems);
		$menuDef = array();
		foreach ($this->linkHandlers as $identifier => $configuration) {
			if (!isset($allowedHandlers[$identifier])) {
				continue;
			}

			/** @var LinkHandlerInterface $handlerInstance */
			$handlerInstance = $configuration['handlerInstance'];
			$isActive = $this->displayedLinkHandlerId === $identifier || !$this->displayedLinkHandlerId && $handlerInstance === $this->currentLinkHandler;
			if ($isActive) {
				$this->displayedLinkHandler = $handlerInstance;
				if (!$this->displayedLinkHandlerId) {
					$this->displayedLinkHandlerId = $this->currentLinkHandlerId;
				}
			}

			if ($configuration['addParams']) {
				$addParams = $configuration['addParams'];
			} else {
				$parameters = GeneralUtility::implodeArrayForUrl('', $this->getUrlParameters(['act' => $identifier]));
				$addParams = 'onclick="jumpToUrl(' . GeneralUtility::quoteJSvalue('?' . ltrim($parameters, '&')) . ');return false;"';
			}
			$menuDef[$identifier] = [
				'isActive' => $isActive,
				'label' => $configuration['label'],
				'url' => '#',
				'addParams' => $addParams,
				'before' => $configuration['displayBefore'],
				'after' => $configuration['displayAfter']
			];
		}

		$menuDef = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($menuDef);

		// if there is no active tab
		if (!$this->displayedLinkHandler) {
			// empty the current link
			$this->currentLink = '';
			$this->currentLinkHandler = NULL;
			$this->currentLinkHandler = '';
			// select first tab
			reset($menuDef);
			$this->displayedLinkHandlerId = key($menuDef);
			$this->displayedLinkHandler = $this->linkHandlers[$this->displayedLinkHandlerId]['handlerInstance'];
			$menuDef[$this->displayedLinkHandlerId]['isActive'] = TRUE;
		}

		return $menuDef;
	}

	/**
	 * Get the allowed items or tabs
	 *
	 * @return string[]
	 */
	protected function getAllowedItems() {
		$allowedItems = array_keys($this->linkHandlers);

		foreach ($this->hookObjects as $hookObject) {
			if (method_exists($hookObject, 'modifyAllowedItems')) {
				$allowedItems = $hookObject->modifyAllowedItems($allowedItems, $this->currentLink);
			}
		}

		// Initializing the action value, possibly removing blinded values etc:
		$blindLinkOptions = isset($this->parameters['params']['blindLinkOptions'])
			? GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkOptions'])
			: [];
		$allowedItems = array_diff($allowedItems, $blindLinkOptions);

		return $allowedItems;
	}

	/**
	 * Get the allowed link attributes
	 *
	 * @return string[]
	 */
	protected function getAllowedLinkAttributes() {
		$allowedLinkAttributes = $this->displayedLinkHandler->getLinkAttributes();

		// Removing link fields if configured
		$blindLinkFields = isset($this->parameters['params']['blindLinkFields'])
			? GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkFields'], TRUE)
			: [];
		$allowedLinkAttributes = array_diff($allowedLinkAttributes, $blindLinkFields);

		return $allowedLinkAttributes;
	}

	/**
	 * Renders the link attributes for the selected link handler
	 *
	 * @return string
	 */
	public function renderLinkAttributeFields() {

		$fieldRenderingDefinitions = $this->getLinkAttributeFieldDefinitions();
		$this->linkAttributeFields = $this->getAllowedLinkAttributes();

		$content = '';
		foreach ($this->linkAttributeFields as $attribute) {
			$content .= $fieldRenderingDefinitions[$attribute];
		}

		// add update button if appropriate
		if ($this->currentLink && $this->displayedLinkHandler === $this->currentLinkHandler && $this->currentLinkHandler->isUpdateSupported()) {
			$content .= '
				<form action="" name="lparamsform" id="lparamsform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkParams">
					<tr><td>
						<input class="btn btn-default t3-js-linkCurrent" type="submit" value="' . $this->getLanguageService()->getLL('update', TRUE) . '" />
					</td></tr>
					</table>
				</form><br /><br />';
		}

		return $content;
	}

	/**
	 * Create an array of link attribute field rendering definitions
	 *
	 * @return string[]
	 */
	protected function getLinkAttributeFieldDefinitions() {
		$lang = $this->getLanguageService();

		$fieldRenderingDefinitions = [];
		$fieldRenderingDefinitions['target'] = '
			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td style="width: 96px;">' . $lang->getLL('target', TRUE) . ':</td>
							<td>
								<input type="text" name="ltarget" id="linkTarget" value="' . htmlspecialchars($this->linkAttributeValues['target']) . '" />
								<select name="ltarget_type" id="targetPreselect">
									<option value=""></option>
									<option value="_top">' . $lang->getLL('top', TRUE) . '</option>
									<option value="_blank">' . $lang->getLL('newWindow', TRUE) . '</option>
								</select>
							</td>
						</tr>
					</table>
				</form>';

		$fieldRenderingDefinitions['title'] = '
				<!--
					Selecting title for link:
				-->
				<form action="" name="ltitleform" id="ltitleform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTitle">
						<tr>
							<td style="width: 96px;">' . $lang->getLL('title', TRUE) . '</td>
							<td><input type="text" name="ltitle" class="typo3-link-input" value="' . htmlspecialchars($this->linkAttributeValues['title']) . '" /></td>
						</tr>
					</table>
				</form>
			';

		$fieldRenderingDefinitions['class'] = '
				<!--
					Selecting class for link:
				-->
				<form action="" name="lclassform" id="lclassform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkClass">
						<tr>
							<td style="width: 96px;">' . $lang->getLL('class', TRUE) . '</td>
							<td><input type="text" name="lclass" class="typo3-link-input" value="' . htmlspecialchars($this->linkAttributeValues['class']) . '" /></td>
						</tr>
					</table>
				</form>
			';

		$fieldRenderingDefinitions['params'] = '
				<!--
					Selecting params for link:
				-->
				<form action="" name="lparamsform" id="lparamsform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkParams">
						<tr>
							<td style="width: 96px;">' . $lang->getLL('params', TRUE) . '</td>
							<td><input type="text" name="lparams" class="typo3-link-input" value="' . htmlspecialchars($this->linkAttributeValues['params']) . '" /></td>
						</tr>
					</table>
				</form>
			';

		return $fieldRenderingDefinitions;
	}

	/**
	 * @param array $overrides
	 *
	 * @return array Array of parameters which have to be added to URLs
	 */
	public function getUrlParameters(array $overrides = NULL) {
		return [
			'act' => isset($overrides['act']) ? $overrides['act'] : $this->displayedLinkHandlerId
		];
	}

	/**
	 * @return string[] Array of body-tag attributes
	 */
	protected function getBodyTagAttributes() {
		$parameters = [];
		$parameters['uid'] = $this->parameters['uid'];
		$parameters['pid'] = $this->parameters['pid'];
		$parameters['itemName'] = $this->parameters['itemName'];
		$parameters['formName'] = $this->parameters['formName'];
		$parameters['fieldChangeFunc'] = $this->parameters['fieldChangeFunc'];
		$parameters['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($this->parameters['fieldChangeFunc']));
		$parameters['params']['allowedExtensions'] = isset($this->parameters['params']['allowedExtensions']) ? $this->parameters['params']['allowedExtensions'] : '';
		$parameters['params']['blindLinkOptions'] = isset($this->parameters['params']['blindLinkOptions']) ? $this->parameters['params']['blindLinkOptions'] : '';
		$parameters['params']['blindLinkFields'] = isset($this->parameters['params']['blindLinkFields']) ? $this->parameters['params']['blindLinkFields']: '';
		$addPassOnParams = GeneralUtility::implodeArrayForUrl('P', $parameters);

		$attributes = $this->displayedLinkHandler->getBodyTagAttributes();
		return array_merge(
			$attributes,
			[
				'data-this-script-url' => strpos($this->thisScript, '?') === FALSE ? $this->thisScript . '?' : $this->thisScript . '&',
				'data-url-parameters' => json_encode($this->getUrlParameters()),
				'data-parameters' => json_encode($this->parameters),
				'data-add-on-params' => $addPassOnParams,
				'data-link-attribute-fields' => json_encode($this->linkAttributeFields)
			]
		);
	}

	/**
	 * Determines whether submitted field change functions are valid
	 * and are coming from the system and not from an external abuse.
	 *
	 * @param bool $handleFlexformSections Whether to handle flexform sections differently
	 * @return bool Whether the submitted field change functions are valid
	 */
	protected function areFieldChangeFunctionsValid($handleFlexformSections = FALSE) {
		$result = FALSE;
		if (isset($this->parameters['fieldChangeFunc']) && is_array($this->parameters['fieldChangeFunc']) && isset($this->parameters['fieldChangeFuncHash'])) {
			$matches = array();
			$pattern = '#\\[el\\]\\[(([^]-]+-[^]-]+-)(idx\\d+-)([^]]+))\\]#i';
			$fieldChangeFunctions = $this->parameters['fieldChangeFunc'];
			// Special handling of flexform sections:
			// Field change functions are modified in JavaScript, thus the hash is always invalid
			if ($handleFlexformSections && preg_match($pattern, $this->parameters['itemName'], $matches)) {
				$originalName = $matches[1];
				$cleanedName = $matches[2] . $matches[4];
				foreach ($fieldChangeFunctions as &$value) {
					$value = str_replace($originalName, $cleanedName, $value);
				}
				unset($value);
			}
			$result = $this->parameters['fieldChangeFuncHash'] === GeneralUtility::hmac(serialize($fieldChangeFunctions));
		}
		return $result;
	}

	/**
	 * Encode a typolink via ajax
	 *
	 * This avoids to implement the encoding functionality again in JS for the browser.
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public static function encodeTypoLink(ServerRequestInterface $request, ResponseInterface $response) {
		$typoLinkParts = $request->getQueryParams();
		if (isset($typoLinkParts['params'])) {
			$typoLinkParts['additionalParams'] = $typoLinkParts['params'];
			unset($typoLinkParts['params']);
		}

		$typoLink = GeneralUtility::makeInstance(TypoLinkCodecService::class)->encode($typoLinkParts);

		$response->getBody()->write(json_encode(['typoLink' => $typoLink]));
		return $response;
	}

	/**
	 * Return the ID of current page
	 *
	 * @return int
	 */
	protected function getCurrentPageId() {
		$pageId = 0;
		$P = $this->parameters;
		if (isset($P['pid'])) {
			$pageId = $P['pid'];
		} elseif (isset($P['itemName'])) {
			// parse data[<table>][<uid>]
			if (preg_match('~data\[([^]]*)\]\[([^]]*)\]~', $P['itemName'], $matches)) {
				$recordArray = BackendUtility::getRecord($matches['1'], $matches['2']);
				if (is_array($recordArray)) {
					$pageId = $recordArray['pid'];
				}
			}
		}
		return $pageId;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @return string
	 */
	public function getDisplayedLinkHandlerId() {
		return $this->displayedLinkHandlerId;
	}

	/**
	 * @return string
	 */
	public function getScriptUrl() {
		return $this->thisScript;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
