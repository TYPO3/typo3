.. include:: /Includes.rst.txt

===================================================================
Feature: #89551 - Add fluidAdditionalAttributes to the form element
===================================================================

See :issue:`89551`

Description
===========

Allows to configure :yaml:`fluidAdditionalAttributes` within a form element:

.. code-block:: yaml

      TYPO3:
        CMS:
          Form:
            prototypes:
              standard:
                formElementsDefinition:
                  Form:
                    renderingOptions:
                      fluidAdditionalAttributes:
                        novalidate: 'novalidate'


Impact
======

For projects using their own Form template, the following attribute can be set on viewhelper :html:`formvh:form` as attribute:
:html:`additionalAttributes="{formvh:translateElementProperty(element: form, property: 'fluidAdditionalAttributes')}"`

.. index:: Fluid, ext:form
