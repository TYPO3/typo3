.. include:: /Includes.rst.txt

.. _feature-103090-1707479280:

====================================================
Feature: #103090 - Make link type label configurable
====================================================

See :issue:`103090`

Description
===========

It is now possible to provide a translated label for custom link types.

For this, a new interface
:php:`\TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface` has been
created, which offers the method :php:`getReadableName` for implementation.
That method can return the translated label.

The default abstract implementation
:php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype` has been enhanced
to implement that interface. Any custom class extending this abstract is
able to override the method :php:`getReadableName` to provide the
custom translation.

Example extending the abstract:
-------------------------------

..  code-block:: php
    :caption: EXT:extension/Classes/Linktype/CustomLinktype.php

    use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;

    #[Autoconfigure(public: true)]
    class CustomLinktype extends AbstractLinktype
    {
        public function getReadableName(): string
        {
            $type = $this->getIdentifier();
            return $this->getLanguageService()->sL(
                'LLL:EXT:linkvalidator_example/Resources/Private/Language/Module/locallang.xlf:linktype_'
                . $type
            ) ?: $type;
        }
    }

Example implementing the interface:
-----------------------------------

..  code-block:: php
    :caption: EXT:extension/Classes/Linktype/CustomLinktype.php

    use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;
    use TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface;

    #[Autoconfigure(public: true)]
    class CustomLinktype implements LinktypeInterface, LabelledLinktypeInterface
    {
        // implement all LinktypeInterface methods:
        // getIdentifier, checkLink, setAdditionalConfig, ...

        // Implement the LabelledLinktypeInterface method getReadableName()
        public function getReadableName(): string
        {
            $type = $this->getIdentifier();
            return $this->getLanguageService()->sL(
                'LLL:EXT:linkvalidator_example/Resources/Private/Language/Module/locallang.xlf:linktype_'
                . $type
            ) ?: $type;
        }
    }

Impact
======

Custom linktype classes should now configure a label by implementing the method
:php:`LabelledLinktypeInterface::getReadableName()`.

All existing custom implementations of the
:php-short:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype` class or the
:php-short:`\TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface`
will continue to work as before, and will just continue to use the internal name of
the link type, instead of a translated label.


.. index:: Backend, ext:linkvalidator
