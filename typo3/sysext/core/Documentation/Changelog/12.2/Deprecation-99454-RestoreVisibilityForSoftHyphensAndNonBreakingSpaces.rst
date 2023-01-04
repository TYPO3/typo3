.. include:: /Includes.rst.txt

.. _deprecation-99454-1672842347:

=================================================================================
Deprecation: #99454 - Restore visibility for soft hyphens and non-breaking spaces
=================================================================================

See :issue:`99454`

Description
===========

Non-breaking spaces and soft hyphens are now visible in
the editor to help the editor to identify them visually.

Keyboard shortcuts are now working for non-breaking spaces
and soft hyphens and use more common defaults:
- ctrl+shift+space for non-breaking space
- ctrl+shift+dash for soft hyphen

The SoftHyphen plugin for CKEditor is now deprecated and
replaced with a new Whitespace Plugin that handles
non-breaking spaces and soft hyphens. Loading the
SoftHyphen will trigger a console warning.


Impact
======

Including the SoftHyphen will trigger a deprecation warning.


Affected installations
======================

All installations that include the Plugin manually.


Migration
=========

Replace the module to resolve the deprecation.

Before
~~~~~~

..  code-block:: yaml
    editor:
        config:
            importModules:
                - '@typo3/rte-ckeditor/plugin/soft-hyphen.js'

After
~~~~~

..  code-block:: yaml
    editor:
        config:
            importModules:
                - '@typo3/rte-ckeditor/plugin/whitespace.js'


.. index:: Backend, JavaScript, RTE, NotScanned, ext:rte_ckeditor
