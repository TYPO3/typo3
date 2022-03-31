.. include:: /Includes.rst.txt

==================================================================
Deprecation: #79441 - Deprecate visibility internal caching arrays
==================================================================

See :issue:`79441`

Description
===========

The following variables have been marked as deprecated in
DataHandler since their visibility will change from public to
protected or even be replaced by a run-time cache.
The documentation states that these are "internal-cache"
variables and hence the visibility public is misleading.

.. code-block:: php

   public $recUpdateAccessCache = [];
   public $recInsertAccessCache = [];
   public $isRecordInWebMount_Cache = [];
   public $isInWebMount_Cache = [];
   public $cachedTSconfig = [];
   public $pageCache = [];


The following variable has been marked as deprecated in the
DataHandler since it is not referenced in the class.

.. code-block:: php

   public $checkWorkspaceCache = [];


Impact
======

These variables should not be accessed in DataHandler from outside
the class since their visibility or even implementation will
change with TYPO3 v9.


Affected Installations
======================

Extensions using one of the above variables.


Migration
=========

None - since public internal

.. index:: PHP-API, Backend
