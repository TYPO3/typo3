.. include:: /Includes.rst.txt

.. _breaking-101192-1688017013:

==============================================================
Breaking: #101192 - Remove fallback for CKEditor removePlugins
==============================================================

See :issue:`101192`

Description
===========

Remove fallback for CKEditor configuration `removePlugins` as a string.

Impact
======

Runtime Javascript errors can occur if the CKEditor configuration
`removePlugins` isn't an array.

Affected installations
======================

TYPO3 installation which have CKEditor configuration `removePlugins`
configured as a string.

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

.. index:: Backend, NotScanned, RTE, ext:rte_ckeditor
