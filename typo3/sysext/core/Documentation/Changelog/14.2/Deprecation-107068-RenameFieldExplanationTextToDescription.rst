.. include:: /Includes.rst.txt

.. _deprecation-107068-1759214357:

==================================================================
Deprecation: #107068 - Rename fieldExplanationText to description
==================================================================

See :issue:`107068`

Description
===========

The configuration option :yaml:`fieldExplanationText` has been deprecated
in favor of :yaml:`description`. The new name better reflects its purpose
and is easier to understand.

This affects form element type definitions in
:yaml:`prototypes.*.formElementsDefinition.*.formEditor` configurations,
including editors, validators, and finishers in any extension.

Impact
======

Using :yaml:`fieldExplanationText` will trigger a PHP deprecation warning.
The migration service will automatically convert :yaml:`fieldExplanationText`
to :yaml:`description` when form configurations are loaded, ensuring
backward compatibility.

Support for :yaml:`fieldExplanationText` will be removed in TYPO3 v15.0.

Affected installations
======================

Any installation using extensions that provide custom form element type
definitions with the configuration option :yaml:`fieldExplanationText`
in their form prototype YAML files (e.g., :file:`Configuration/Form/*.yaml`
or :file:`Configuration/Yaml/FormSetup.yaml`).

Migration
=========

Rename any occurrence of :yaml:`fieldExplanationText` to :yaml:`description`
in your form element type definition YAML files (typically located in
:file:`Configuration/Yaml/FormElements/*.yaml`).

Example migration:

.. code-block:: yaml

   # Before (deprecated)
   formEditor:
     editors:
       200:
         identifier: placeholder
         label: Placeholder
         fieldExplanationText: Enter the placeholder text

   # After
   formEditor:
     editors:
       200:
         identifier: placeholder
         label: Placeholder
         description: Enter the placeholder text

.. index:: Backend, ext:form, NotScanned
