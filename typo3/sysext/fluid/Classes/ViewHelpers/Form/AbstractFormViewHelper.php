<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Abstract Form ViewHelper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 */
abstract class AbstractFormViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Prefixes / namespaces the given name with the form field prefix
     *
     * @param string $fieldName field name to be prefixed
     * @return string namespaced field name
     */
    protected function prefixFieldName($fieldName)
    {
        if ($fieldName === null || $fieldName === '') {
            return '';
        }
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')) {
            return $fieldName;
        }
        $fieldNamePrefix = (string)$viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        if ($fieldNamePrefix === '') {
            return $fieldName;
        }
        $fieldNameSegments = explode('[', $fieldName, 2);
        $fieldName = $fieldNamePrefix . '[' . $fieldNameSegments[0] . ']';
        if (count($fieldNameSegments) > 1) {
            $fieldName .= '[' . $fieldNameSegments[1];
        }
        return $fieldName;
    }

    /**
     * Renders a hidden form field containing the technical identity of the given object.
     *
     * @param object $object Object to create the identity field for
     * @param string $name Name
     * @return string A hidden field containing the Identity (UID in TYPO3 Flow, uid in Extbase) of the given object or NULL if the object is unknown to the persistence framework
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\Argument::setValue()
     */
    protected function renderHiddenIdentityField($object, $name)
    {
        if ($object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            $object = $object->_loadRealInstance();
        }
        if (!is_object($object)
            || !($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject)
            || ($object->_isNew() && !$object->_isClone())) {
            return '';
        }
        // Intentionally NOT using PersistenceManager::getIdentifierByObject here!!
        // Using that one breaks re-submission of data in forms in case of an error.
        $identifier = $object->getUid();
        if ($identifier === null) {
            return LF . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . LF;
        }
        $name = $this->prefixFieldName($name) . '[__identity]';
        $this->registerFieldNameForFormTokenGeneration($name);

        return LF . '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($identifier) . '" />' . LF;
    }

    /**
     * Register a field name for inclusion in the HMAC / Form Token generation
     *
     * @param string $fieldName name of the field to register
     */
    protected function registerFieldNameForFormTokenGeneration($fieldName)
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames')) {
            $formFieldNames = $viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames');
        } else {
            $formFieldNames = [];
        }
        $formFieldNames[] = $fieldName;
        $viewHelperVariableContainer->addOrUpdate(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames', $formFieldNames);
    }
}
