..  include:: /Includes.rst.txt

..  _feature-94822-1783091790:

===============================================================
Feature: #94822 - Multiple records with record browser in forms
===============================================================

See :issue:`94822`

Description
===========

The record browser in forms can now be used to add multiple records to a property,
if configured accordingly.

When the option :yaml:`maxItems` of the :yaml:`Inspector-Typo3WinBrowserEditor` is
greater than 1, the UIDs of the selected records are added as a comma-separated list:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               MyCustomElement:
                 formEditor:
                   editors:
                     # ...
                     300:
                       identifier: myRecords
                       # ...
                       minItems: 1
                       maxItems: 10
                       propertyPath: properties.myRecordUids
                       # ...
                       propertyValidators:
                         10: IntegerList
                         # ...

Custom logic must be added to actually make use of the multiple values.

Whenever :yaml:`minItems` or :yaml:`maxItems` is configured, the number of
selected records is validated automatically through the new :yaml:`ItemCount`
property validator, without the need to add it to :yaml:`propertyValidators`.

There is also a new property validator:

*   :yaml:`IntegerList` checks whether all elements in the comma-separated list
    are integers.


Impact
======

Form definitions can be set up to allow editors the selection of multiple database
records and then render them using custom logic.

To avoid conflicts with existing configurations, :yaml:`minItems` is set to 0 and
:yaml:`maxItems` to 1 by default.

..  index:: Backend, ext:form
