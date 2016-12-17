.. include:: ../../Includes.txt

=======================================================
Breaking: #78468 - Remove ExtDirect from EXT:workspaces
=======================================================

See :issue:`78468`

Description
===========

To remove ExtJS the ExtDirect component has been removed too.
A new class :php:`TYPO3\CMS\Workspaces\Controller\AjaxDispatcher` has been added to implement the ExtDirect router functionality.
This class is callable by a new AJAX route with the name `workspace_dispatch`.


Impact
======

The following classes have been moved:

* EXT:workspaces/Classes/ExtDirect/AbstractHandler.php
  => EXT:workspaces/Classes/Controller/Remote/AbstractHandler.php

* EXT:workspaces/Classes/ExtDirect/ActionHandler.php
  => EXT:workspaces/Classes/Controller/Remote/ActionHandler.php

* EXT:workspaces/Classes/ExtDirect/MassActionHandler.php
  => EXT:workspaces/Classes/Controller/Remote/MassActionHandler.php

* EXT:workspaces/Classes/ExtDirect/ExtDirectServer.php
  => EXT:workspaces/Classes/Controller/Remote/RemoteServer.php


Affected Installations
======================

Any TYPO3 installation using the previously classes.


Migration
=========

Use the new classes as mentioned above.

.. index:: Backend, JavaScript, ext:workspaces
