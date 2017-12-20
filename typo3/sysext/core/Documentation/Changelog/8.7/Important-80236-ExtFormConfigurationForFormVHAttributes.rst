.. include:: ../../Includes.txt

=================================================================
Important: #80236 - EXT:form Configuration for form VH attributes
=================================================================

See :issue:`80236`

Description
===========

Move some fixed configurations from within the fluid templates into the
form settings

* f:form -> additionalParams
* f:form -> addQueryString
* f:form -> argumentsToBeExcludedFromQueryString
* f:form -> action
* f:form -> enctype
* f:form -> method

within the EXT:Form configuration.

.. code-block:: yaml

    TYPO3:
        CMS:
            Form:
                prototypes:
                    <prototypeName>:
                        formElementsDefinition:
                            Form:
                                renderingOptions:
                                    addQueryString: false
                                    argumentsToBeExcludedFromQueryString: []
                                    additionalParams: []
                                    controllerAction: perform
                                    httpMethod: post
                                    httpEnctype: 'multipart/form-data'


Impact
======

An integrator can configure the FLUID form properties

f:form -> additionalParams
f:form -> addQueryString
f:form -> argumentsToBeExcludedFromQueryString
f:form -> action
f:form -> enctype
f:form -> method


.. index:: Frontend, ext:form, Fluid
