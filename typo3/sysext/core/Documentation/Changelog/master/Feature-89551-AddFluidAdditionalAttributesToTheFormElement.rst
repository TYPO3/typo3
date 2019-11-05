.. include:: ../../Includes.txt

===================================================================
Feature: #89551 - Add fluidAdditionalAttributes to the form element
===================================================================

See :issue:`89551`

Description
===========

Allows to configure fluidAdditionalAttributes within form element:
`TYPO3:
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

For projects using it's own Form template, the following attribute can be set on viewhelper formvh:form as attribute:
`additionalAttributes="{formvh:translateElementProperty(element: form, property: 'fluidAdditionalAttributes')}"`

.. index:: Fluid, ext:form
