.. include:: /Includes.rst.txt

.. _important-97517:

==================================================================================
Important: #97517 - Remove the superfluous namespace within the form configuration
==================================================================================

See :issue:`97517`

Description
===========

The superfluous vendor namespace (:yaml:`TYPO3.CMS.Form`) has been removed from the form configuration.
That way the configuration of the form framework is less deeply nested.

The compatibility to the notation with the vendor namespace is
maintained, both notations are still possible. Nevertheless we recommend not to apply the vendor namespace.

Migration
=========

This is how the legacy configuration with vendor namespace looks like:

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                # ...
          # ...

This is how the new (preferred) configuration without vendor namespace looks like:

..  code-block:: yaml

    prototypes:
      standard:
        formElementsDefinition:
                # ...
    # ...

.. index:: Backend, ext:form
