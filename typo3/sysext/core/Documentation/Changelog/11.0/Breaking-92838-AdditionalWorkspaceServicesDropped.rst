.. include:: /Includes.rst.txt

========================================================
Breaking: #92838 - Additional workspace services dropped
========================================================

See :issue:`92838`

Description
===========

Back in the ExtJS era, the workspace backend module had two PHP classes designed
for extensions to add additional columns and JavaScript handling to the module.
With the transition to a native JavaScript implementation of the workspace module
in TYPO3 v8, this stopped working. The related PHP classes have now been removed.


Impact
======

There was one specific customer this feature has been implemented for. It does
not use it anymore. Considering the fact the feature has been broken since years,
there should be little to no impact for any instance.

The following classes and interfaces have been removed:

* :php:`TYPO3\CMS\Workspaces\ColumnDataProviderInterface`
* :php:`TYPO3\CMS\Workspaces\Service\AdditionalColumnService`
* :php:`TYPO3\CMS\Workspaces\Service\AdditionalResourceService`


Affected Installations
======================

Instances with extensions using above classes or interfaces. The extension
scanner will find usages with a strong match.


Migration
=========

No migration available.

.. index:: Backend, JavaScript, PHP-API, FullyScanned, ext:workspaces
