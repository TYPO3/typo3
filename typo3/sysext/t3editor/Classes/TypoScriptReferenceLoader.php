<?php
namespace TYPO3\CMS\T3editor;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads TSref information from a XML file an responds to an AJAX call.
 */
class TypoScriptReferenceLoader
{
    /**
     * @var \DOMDocument
     */
    protected $xmlDoc;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:t3editor/Resources/Private/Language/locallang.xlf');
    }

    /**
     * General processor for AJAX requests.
     * Called by AjaxRequestHandler
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Load the TSref XML information:
        $this->loadFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3editor') . 'Resources/Private/tsref.xml');
        $fetch = isset($parsedBody['fetch']) ? $parsedBody['fetch'] : $queryParams['fetch'];
        $content = null;

        switch ($fetch) {
            case 'types':
                $response->getBody()->write(json_encode($this->getTypes()));
                break;
            case 'description':
                $typeId = isset($parsedBody['typeId']) ? $parsedBody['typeId'] : $queryParams['typeId'];
                $parameterName = isset($parsedBody['parameterName']) ? $parsedBody['parameterName'] : $queryParams['parameterName'];
                $response = $this->getDescription($typeId, $parameterName);
                $response->withHeader('Content-Type', 'text/html; charset=utf8');
                break;
        }
        return $response;
    }

    /**
     * Load XML file
     *
     * @param string $filepath
     * @return void
     */
    protected function loadFile($filepath)
    {
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $this->xmlDoc = new \DOMDocument('1.0', 'utf-8');
        $this->xmlDoc->loadXML(file_get_contents($filepath));
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        // @TODO: oliver@typo3.org: I guess this is not required here
        $this->xmlDoc->saveXML();
    }

    /**
     * Get types from XML
     *
     * @return array
     */
    protected function getTypes()
    {
        $types = $this->xmlDoc->getElementsByTagName('type');
        $typeArr = [];
        foreach ($types as $type) {
            $typeId = $type->getAttribute('id');
            $typeName = $type->getAttribute('name');
            if (!$typeName) {
                $typeName = $typeId;
            }
            $properties = $type->getElementsByTagName('property');
            $propArr = [];
            foreach ($properties as $property) {
                $p = [];
                $p['name'] = $property->getAttribute('name');
                $p['type'] = $property->getAttribute('type');
                $propArr[$property->getAttribute('name')] = $p;
            }
            $typeArr[$typeId] = [];
            $typeArr[$typeId]['properties'] = $propArr;
            $typeArr[$typeId]['name'] = $typeName;
            if ($type->hasAttribute('extends')) {
                $typeArr[$typeId]['extends'] = $type->getAttribute('extends');
            }
        }
        return $typeArr;
    }

    /**
     * Get description
     *
     * @param string $typeId
     * @param string $parameterName
     * @return ResponseInterface
     */
    protected function getDescription($typeId, $parameterName = '')
    {
        /** @var \TYPO3\CMS\Core\Http\Response $response */
        $response = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\Response::class);
        if (!$typeId) {
            $response->getBody()->write($GLOBALS['LANG']->getLL('typeIDMissing'));
            $response = $response->withStatus(500);
            return $response;
        }
        // getElementById does only work with schema
        $type = $this->getType($typeId);
        // Retrieve propertyDescription
        if ($parameterName) {
            $properties = $type->getElementsByTagName('property');
            foreach ($properties as $propery) {
                $propName = $propery->getAttribute('name');
                if ($propName == $parameterName) {
                    $descriptions = $propery->getElementsByTagName('description');
                    if ($descriptions->length) {
                        $description = $descriptions->item(0)->textContent;
                        $description = htmlspecialchars($description);
                        $description = nl2br($description);
                        $response->getBody()->write($description);
                        break;
                    }
                }
            }
        }
        return $response;
    }

    /**
     * Get type
     *
     * @param string $typeId
     * @return \DOMNode
     */
    protected function getType($typeId)
    {
        $types = $this->xmlDoc->getElementsByTagName('type');
        foreach ($types as $type) {
            if ($type->getAttribute('id') == $typeId) {
                return $type;
            }
        }
    }
}
