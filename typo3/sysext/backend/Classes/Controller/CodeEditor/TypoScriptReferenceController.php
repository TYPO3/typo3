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

namespace TYPO3\CMS\Backend\Controller\CodeEditor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads TSref information from a XML file and responds to an AJAX call.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptReferenceController
{
    /**
     * Load TypoScript reference
     */
    public function loadReference(ServerRequestInterface $request): ResponseInterface
    {
        // Load the TSref XML information
        $xmlDoc = new \DOMDocument('1.0', 'utf-8');
        $xmlDoc->loadXML(file_get_contents(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/tsref.xml')));

        return new JsonResponse($this->getTypes($xmlDoc));
    }

    /**
     * Get types from XML
     */
    protected function getTypes(\DOMDocument $xmlDoc): array
    {
        $types = $xmlDoc->getElementsByTagName('type');
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
}
