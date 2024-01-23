.. include:: /Includes.rst.txt

.. _feature-102865-1705587633:

====================================================================
Feature: #102865 - PSR-14 event for modifying loaded form definition
====================================================================

See :issue:`102865`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent`
has been introduced which allows extensions to modify loaded form definitions.

The event is being dispatched after :php:`FormPersistenceManager` has loaded
the definition from either the cache or the filesystem. In latter case, the
event is dispatched after :php:`FormPersistenceManager` has stored the loaded
definition in cache. This means, it's always possible to modify the cached
version. However, the modified form definition is then overridden by TypoScript,
in case a corresponding :typoscript:`formDefinitionOverrides` exists.

The event features the following methods:

* :php:`getFormDefinition()` - Returns the loaded form definition
* :php:`setFormDefinition()` - Allows to modify the loaded form definition
* :php:`getPersistenceIdentifier()` - Returns the persistence identifier, used to load the definition
* :php:`getCacheKey()` - Returns the calculated cache key

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent;

    final class AfterFormDefinitionLoadedEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterFormDefinitionLoadedEvent $event): void
        {
            if ($event->getPersistenceIdentifier() === '1:/form_definitions/contact.form.yaml') {
                $formDefinition = $event->getFormDefinition();
                $formDefinition['label'] = 'some new label';
                $event->setFormDefinition($formDefinition);
            }
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to fully modify any loaded
form definition, before being overridden by TypoScript.

.. index:: PHP-API, YAML, ext:form
