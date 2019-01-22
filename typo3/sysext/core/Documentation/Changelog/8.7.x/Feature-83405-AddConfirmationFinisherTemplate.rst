.. include:: ../../Includes.txt

===================================================
Feature: #83405 - add ConfirmationFinisher template
===================================================

See :issue:`83405`

Description
===========

The ConfirmationFinisher message is now rendered within a fluid template to allow styling of the message.
Furthermore, the FormRuntime (and thus all form element values) and the finisherVariableProvider are available in the template [1].
Custom variables can be added globally within the form setup or at form level in the form definition [2].
By using a fluid template and the associated html escaping, the display of the ConfirmationFinisher message is protected against XSS / html injection attacks.
The ext: form supplied fluid template does not include any HTML wrapping to remain compatible with existing installations, but it is possible to implement your own template [3].

[1] Template variables
----------------------

* :html:`{form}` - Object for access to submitted form element values (https://docs.typo3.org/typo3cms/extensions/form/Concepts/FrontendRendering/Index.html#accessing-form-values)
* :html:`{finisherVariableProvider}` - Object with data from previous finishers (https://docs.typo3.org/typo3cms/extensions/form/Concepts/FrontendRendering/Index.html#share-data-between-finishers)
* :html:`{message}` - The confirmation message

[2] custom template variables
-----------------------------

global within the form setup:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              finishersDefinition:
                Confirmation:
                  options:
                    variables:
                      foo: bar

per form within the form definition:

.. code-block:: yaml

    finishers:
      -
        identifier: Confirmation
        options:
          message: 'Thx'
          variables:
            foo: bar

[3] custom Template
-------------------

form setup:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              finishersDefinition:
                Confirmation:
                  options:
                    templateRootPaths:
                      20: 'EXT:my_site_package/Resources/Private/Templates/Form/Finishers/Confirmation/'

Impact
======

Integrators can use a ConfirmationFinisher message within a fluid template.
Integrators can use additional information such as form element values within the template.
The ConfirmationFinisher message is protected against XSS / html injection attacks.

.. index:: Frontend, ext:form, NotScanned
