.. include:: /Includes.rst.txt

.. _deprecation-97019:

===========================================================
Deprecation: #97019 - Deprecate order of validation message
===========================================================

See :issue:`97019`

Description
===========

The form framework ships a "date range validator". The validator can for example
be added via the form editor to the "date" form element. Especially when it comes
to this element, the order of the available fields is not in line with all of the
other form elements. The field "Custom error message" (validationErrorMessage)
is usually the last field. To further streamline the UI, the order has been
adapted for the "date" validator.

Since the order can only be adjusted by changing the key within the form
configuration, this patch adds according comments to the form configuration.
The breaking change will be done with TYPO3 v13.0.

Impact
======

Since the YAML keys within the form configuration of the "date" form element
will change in TYPO3 v13, custom configurations/implementations can fail. An
according comment has been added to the configuration file of the form
framework. Furthermore, the new key has been reserved.

Current configuration in TYPO3 v12 (simplified):

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Date:
                  formEditor:
                    propertyCollections:
                      validators:
                        10:
                          identifier: DateRange
                          editors:
                            # Deprecated since v12, will be removed in v13
                            # Instead of using the key 200, the validationErrorMessage will be moved to the key 400
                            200:
                              identifier: validationErrorMessage
                              # ...
                            250:
                              identifier: minimum
                              # ...
                            300:
                              identifier: maximum
                              # ...

New configuration in TYPO3 v13 (simplified):

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Date:
                  formEditor:
                    propertyCollections:
                      validators:
                        10:
                          identifier: DateRange
                          editors:
                            250:
                              identifier: minimum
                              # ...
                            300:
                              identifier: maximum
                              # ...
                            400:
                              identifier: validationErrorMessage
                              # ...

As you can see the key :yaml:`200` is not in use anymore. Instead, a new key
:yaml:`400` has been introduced. The new configuration is commented in TYPO3 v12
and will be enabled in TYPO3 v13.

Affected Installations
======================

All TYPO3 installations are affected as soon as the form configuration of the
"DateRange" validator of the "date" form element has been adapted. In detail,
installations where the above mentioned keys have been set or unset need to
be migrated to the new configuration.

Migration
=========

Check your form configuration accordingly and adapt your custom configuration.
That is, check if you set/unset the above mentioned keys.

.. index:: Backend, NotScanned, ext:form
