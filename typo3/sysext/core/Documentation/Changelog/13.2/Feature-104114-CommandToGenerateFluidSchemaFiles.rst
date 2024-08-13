.. include:: /Includes.rst.txt

.. _feature-104114-1719419341:

=========================================================
Feature: #104114 - Command to generate Fluid schema files
=========================================================

See :issue:`104114`

Description
===========

With Fluid Standalone 2.12, a new implementation of the XSD schema generator has
been introduced, which was previously a separate composer package. These XSD files
allow IDEs to provide autocompletion for ViewHelper arguments in Fluid templates,
provided that they are included in the template by using the xmlns syntax:

.. code-block:: html

    <html
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:my="http://typo3.org/ns/Vendor/MyPackage/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >

A new CLI command has been defined to apply Fluid's new schema generator to TYPO3's
Fluid integration. New Fluid APIs are used to find all ViewHelpers that exist in
the current project (based on the composer autoloader). Then, TYPO3's configuration
is checked for any merged Fluid namespaces (like `f:`, which consists of both
Fluid Standalone and EXT:fluid ViewHelpers which in some cases override each other).

After that consolidation, `*.xsd` files are created in `var/transient/` using another
API from Fluid Standalone. These files which will automatically get picked up by
supporting IDEs (like PhpStorm) to provide autocompletion in template files.


Impact
======

To get autocompletion for all available ViewHelpers in supporting IDEs, the following
CLI command can be executed in local development environments:

..  code-block:: bash

    vendor/bin/typo3 fluid:schema:generate

.. index:: CLI, Fluid, ext:fluid
