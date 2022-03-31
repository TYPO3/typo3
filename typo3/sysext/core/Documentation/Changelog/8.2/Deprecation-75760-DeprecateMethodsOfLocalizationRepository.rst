
.. include:: /Includes.rst.txt

=================================================================
Deprecation: #75760 - Deprecate methods of LocalizationRepository
=================================================================

See :issue:`75760`

Description
===========

The following methods have been marked as deprecated:

- :php:`LocalizationRepository::getExcludeQueryPart()`
- :php:`LocalizationRepository::getAllowedLanguagesForBackendUser()`


Impact
======

Using the mentioned methods will trigger a deprecation log entry


Affected Installations
======================

Any installation with a 3rd party extension that uses one of the named methods.


Migration
=========

Instead of :php:`LocalizationRepository::getExcludeQueryPart()` configure the query restrictions yourself:

.. code-block:: php

    $queryBuilder->getRestrictions()
        ->removeAll()
        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
        ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

Instead of :php:`LocalizationRepository::getAllowedLanguagesForBackendUser()` add
the required conditions to your query yourself.

.. index:: PHP-API, Backend, Database
