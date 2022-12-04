.. include:: /Includes.rst.txt

.. _deprecation-98613:

===================================================================
Deprecation: #98613 - CKEditor removePlugin configuration as string
===================================================================

See :issue:`98613`

Description
===========

The :yaml:`removePlugins` option needs to be assigned as an array in CKEditor 5.
While we recommended passing the option already as an array, CKEditor 4 needed a
comma-separated string.

The conversion was only handled if the integrator passed an array, which means
if someone already provided a comma-separated string the option was simply
passed as-is to the editor configuration.

To avoid JavaScript errors, we are going to migrate it to array for now. The
possibility to pass the option as a string is deprecated and will be removed
with TYPO3 v13.


Impact
======

Passing the CKEditor configuration :yaml:`removePlugins` as string will trigger
a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations that pass the CKEditor configuration :yaml:`removePlugins` as
string.


Migration
=========

Adjust your CKEditor configuration and pass :yaml:`removePlugins` as array.


Before
------

..  code-block:: yaml

    editor:
        config:
            removePlugins: image

After
-----

..  code-block:: yaml

    editor:
        config:
            removePlugins:
                - image

.. index:: RTE, NotScanned, ext:rte_ckeditor
