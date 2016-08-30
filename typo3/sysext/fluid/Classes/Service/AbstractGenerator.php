<?php
namespace TYPO3\CMS\Fluid\Service;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Common base class for XML generators.
 */
abstract class AbstractGenerator
{
    /**
     * The reflection class for AbstractViewHelper. Is needed quite often, that's why we use a pre-initialized one.
     *
     * @var \TYPO3\CMS\Extbase\Reflection\ClassReflection
     */
    protected $abstractViewHelperReflectionClass;

    /**
     * The doc comment parser.
     *
     * @var \TYPO3\CMS\Extbase\Reflection\DocCommentParser
     * @inject
     */
    protected $docCommentParser;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     * @inject
     */
    protected $reflectionService;

    /**
     * Constructor. Sets $this->abstractViewHelperReflectionClass
     *
     */
    public function __construct()
    {
        \TYPO3\CMS\Fluid\Fluid::$debugMode = true; // We want ViewHelper argument documentation
        $this->abstractViewHelperReflectionClass = new \TYPO3\CMS\Extbase\Reflection\ClassReflection(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class);
    }

    /**
     * Get all class names inside this namespace and return them as array.
     *
     * @param string $namespace
     * @return array Array of all class names inside a given namespace.
     */
    protected function getClassNamesInNamespace($namespace)
    {
        $affectedViewHelperClassNames = [];

        $allViewHelperClassNames = $this->reflectionService->getAllSubClassNamesForClass(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class);
        foreach ($allViewHelperClassNames as $viewHelperClassName) {
            if ($this->reflectionService->isClassAbstract($viewHelperClassName)) {
                continue;
            }
            if (strncmp($namespace, $viewHelperClassName, strlen($namespace)) === 0) {
                $affectedViewHelperClassNames[] = $viewHelperClassName;
            }
        }
        sort($affectedViewHelperClassNames);
        return $affectedViewHelperClassNames;
    }

    /**
     * Get a tag name for a given ViewHelper class.
     * Example: For the View Helper TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper, and the
     * namespace prefix TYPO3\CMS\Fluid\ViewHelpers\, this method returns "form.select".
     *
     * @param string $className Class name
     * @param string $namespace Base namespace to use
     * @return string Tag name
     */
    protected function getTagNameForClass($className, $namespace)
    {
        /// Strip namespace from the beginning and "ViewHelper" from the end of the class name
        $strippedClassName = substr($className, strlen($namespace), -10);
        $classNameParts = explode(\TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR, $strippedClassName);
        return implode(
            '.',
            array_map(
                function ($element) {
                    return lcfirst($element);
                },
                $classNameParts
            )
        );
    }

    /**
     * Add a child node to $parentXmlNode, and wrap the contents inside a CDATA section.
     *
     * @param \SimpleXMLElement $parentXmlNode Parent XML Node to add the child to
     * @param string $childNodeName Name of the child node
     * @param string $childNodeValue Value of the child node. Will be placed inside CDATA.
     * @return \SimpleXMLElement the new element
     */
    protected function addChildWithCData(\SimpleXMLElement $parentXmlNode, $childNodeName, $childNodeValue)
    {
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $parentDomNode = dom_import_simplexml($parentXmlNode);
        $domDocument = new \DOMDocument();

        $childNode = $domDocument->appendChild($domDocument->createElement($childNodeName));
        $childNode->appendChild($domDocument->createCDATASection($childNodeValue));
        $childNodeTarget = $parentDomNode->ownerDocument->importNode($childNode, true);
        $parentDomNode->appendChild($childNodeTarget);
        $returnValue = simplexml_import_dom($childNodeTarget);
        libxml_disable_entity_loader($previousValueOfEntityLoader);

        return $returnValue;
    }
}
