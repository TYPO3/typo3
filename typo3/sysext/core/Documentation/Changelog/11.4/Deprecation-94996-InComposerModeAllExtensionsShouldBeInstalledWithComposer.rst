.. include:: /Includes.rst.txt

========================================================================================
Deprecation: #94996 - In Composer Mode, all Extensions should be installed with Composer
========================================================================================

See :issue:`94996`

Description
===========

Having extensions within :file:`typo3conf/ext` in Composer mode, which have not
been installed with Composer, has been marked as deprecated.

TYPO3 Extensions are Composer packages and therefore Composer mechanisms should
be used to install them properly in the project, and not placed manually in their
target location :file:`typo3conf/ext`


Impact
======

A PHP :php:`E_USER_DEPRECATED` error is raised for any extension that is not
installed with Composer, if the instance is composer based.


Affected Installations
======================

Composer based TYPO3 projects, that have extensions directly in :file:`typo3conf/ext`,
for instance under version control.


Migration
=========

Composer based TYPO3 projects, that have extensions directly in :file:`typo3conf/ext`
under version control, should migrate them to be installed using the Composer path
repository mechanism:


.. code-block:: json

    {
        "repositories": [
            {
                "type": "path",
                "url": "./packages/*/"
            },
        ],
        "require": {
            "my/example-extension": "@dev",
        }
    }


Now, when `example-extension` is located in :file:`packages/example-extension`, it is picked
up by composer and symlinked into :file:`typo3conf/ext/example_extension`.

.. index:: CLI, NotScanned, ext:core
