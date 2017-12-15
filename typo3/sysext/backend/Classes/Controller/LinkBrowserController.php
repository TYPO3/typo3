<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Extended controller for link browser
 */
class LinkBrowserController extends AbstractLinkBrowserController
{
    /**
     * Initialize $this->currentLinkParts
     */
    protected function initCurrentUrl()
    {
        $currentLink = isset($this->parameters['currentValue']) ? trim($this->parameters['currentValue']) : '';
        $currentLinkParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($currentLink);
        $currentLinkParts['params'] = $currentLinkParts['additionalParams'];
        unset($currentLinkParts['additionalParams']);

        if (!empty($currentLinkParts['url'])) {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $data = $linkService->resolve($currentLinkParts['url']);
            $currentLinkParts['type'] = $data['type'];
            unset($data['type']);
            $currentLinkParts['url'] = $data;
        }

        $this->currentLinkParts = $currentLinkParts;

        parent::initCurrentUrl();
    }

    /**
     * Initialize document template object
     */
    protected function initDocumentTemplate()
    {
        parent::initDocumentTemplate();

        if (!$this->areFieldChangeFunctionsValid() && !$this->areFieldChangeFunctionsValid(true)) {
            $this->parameters['fieldChangeFunc'] = [];
        }
        unset($this->parameters['fieldChangeFunc']['alert']);
        $update = [];
        foreach ($this->parameters['fieldChangeFunc'] as $v) {
            $update[] = 'parent.opener.' . $v;
        }
        $inlineJS = implode(LF, $update);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJquery();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter', 'function(FormEngineLinkBrowserAdapter) {
			FormEngineLinkBrowserAdapter.updateFunctions = function() {' . $inlineJS . '};
		}');
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
    public function encodeTypoLink(ServerRequestInterface $request, ResponseInterface $response)
    {
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
     * Determines whether submitted field change functions are valid
     * and are coming from the system and not from an external abuse.
     *
     * @param bool $handleFlexformSections Whether to handle flexform sections differently
     * @return bool Whether the submitted field change functions are valid
     */
    protected function areFieldChangeFunctionsValid($handleFlexformSections = false)
    {
        $result = false;
        if (isset($this->parameters['fieldChangeFunc']) && is_array($this->parameters['fieldChangeFunc']) && isset($this->parameters['fieldChangeFuncHash'])) {
            $matches = [];
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
            $result = hash_equals(GeneralUtility::hmac(serialize($fieldChangeFunctions)), $this->parameters['fieldChangeFuncHash']);
        }
        return $result;
    }

    /**
     * Get attributes for the body tag
     *
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        $parameters = parent::getBodyTagAttributes();

        $formEngineParameters['fieldChangeFunc'] = $this->parameters['fieldChangeFunc'];
        $formEngineParameters['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($this->parameters['fieldChangeFunc']));

        $parameters['data-add-on-params'] .= GeneralUtility::implodeArrayForUrl('P', $formEngineParameters);

        return $parameters;
    }

    /**
     * Return the ID of current page
     *
     * @return int
     */
    protected function getCurrentPageId()
    {
        $pageId = 0;
        $browserParameters = $this->parameters;
        if (isset($browserParameters['pid'])) {
            $pageId = $browserParameters['pid'];
        } elseif (isset($browserParameters['itemName'])) {
            // parse data[<table>][<uid>]
            if (preg_match('~data\[([^]]*)\]\[([^]]*)\]~', $browserParameters['itemName'], $matches)) {
                $recordArray = BackendUtility::getRecord($matches['1'], $matches['2']);
                if (is_array($recordArray)) {
                    $pageId = $recordArray['pid'];
                }
            }
        }
        return (int)BackendUtility::getTSCpidCached($browserParameters['table'], $browserParameters['uid'], $pageId)[0];
    }
}
