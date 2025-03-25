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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Abstract Form ViewHelper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 * Note this set of ViewHelpers is tailored to be used only in extbase context.
 */
abstract class AbstractFormViewHelper extends AbstractTagBasedViewHelper
{
    protected PersistenceManagerInterface $persistenceManager;
    protected PageRenderer $pageRenderer;

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Prefixes / namespaces the given name with the form field prefix
     */
    protected function prefixFieldName(string $fieldName): string
    {
        if ($fieldName === '') {
            return '';
        }
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$viewHelperVariableContainer->exists(FormViewHelper::class, 'fieldNamePrefix')) {
            return $fieldName;
        }
        $fieldNamePrefix = (string)$viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix');
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
     * @param mixed $object Object to create the identity field for. Non-objects are ignored.
     * @param string|null $name Name
     * @return string A hidden field containing the Identity (uid) of the given object
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\Argument::setValue()
     */
    protected function renderHiddenIdentityField(mixed $object, ?string $name): string
    {
        if ($object instanceof LazyLoadingProxy) {
            $object = $object->_loadRealInstance();
        }
        if (!is_object($object)
            || !($object instanceof AbstractDomainObject)
            || ($object->_isNew() && !$object->_isClone())) {
            return '';
        }
        // Intentionally NOT using PersistenceManager::getIdentifierByObject here.
        // Using that one breaks re-submission of data in forms in case of an error.
        $identifier = $object->getUid();
        if ($identifier === null) {
            return LF . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . LF;
        }
        $name = $this->prefixFieldName($name ?? '') . '[__identity]';
        $this->registerFieldNameForFormTokenGeneration($name);

        $endingSlash = ($this->shouldUseXHtmlSlash() ? '/' : '');
        return LF . '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars((string)$identifier) . '" ' . $endingSlash . '>' . LF;
    }

    /**
     * Register a field name for inclusion in the HMAC / Form Token generation
     */
    protected function registerFieldNameForFormTokenGeneration(string $fieldName): void
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(FormViewHelper::class, 'formFieldNames')) {
            $formFieldNames = $viewHelperVariableContainer->get(FormViewHelper::class, 'formFieldNames');
        } else {
            $formFieldNames = [];
        }
        $formFieldNames[] = $fieldName;
        $viewHelperVariableContainer->addOrUpdate(FormViewHelper::class, 'formFieldNames', $formFieldNames);
    }

    protected function shouldUseXHtmlSlash(): bool
    {
        return $this->pageRenderer->getDocType()->isXmlCompliant();
    }
}
