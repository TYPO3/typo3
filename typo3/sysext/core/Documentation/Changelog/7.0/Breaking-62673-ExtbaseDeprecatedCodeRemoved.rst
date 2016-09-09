
.. include:: ../../Includes.txt

=====================================================
Breaking: #62673 - Deprecated extbase code is removed
=====================================================

See :issue:`62673`

Description
===========

Generic\Qom\Statement
---------------------

You may no longer use bound variables without using a prepared statement.

ActionController
----------------

Support for old view configuration options templateRootPath, layoutRootPath and partialRootPath is dropped.
Use the new options with fallback mechanism.


Removed PHP classes
-------------------

* QueryObjectModelConstantsInterface
* QueryObjectModelFactoryInterface


Removed PHP class members
-------------------------

* ActionController::$viewObjectNamePattern is removed without replacement
* Repository::$backend is removed, use persistence manager instead


Removed PHP methods
-------------------

* ObjectManager::create() is removed, use ObjectManager::get() instead
* ObjectManagerInterface::create() is removed
* Persistence\Generic\Backend::replaceObject() is removed without replacement
* QuerySettingsInterface::setReturnRawQueryResult() is removed without replacement
* QuerySettingsInterface::getReturnRawQueryResult() is removed, use the parameter on $query->execute() directly
* Typo3QuerySettings::setSysLanguageUid() is removed, use setLanguageUid() instead
* Typo3QuerySettings::getSysLanguageUid() is removed, use getLanguageUid() instead


Impact
======

A call to any of the aforementioned methods by third party code will result in a fatal PHP error.


Affected installations
======================

Any installation which contains third party code still using these deprecated methods.


Migration
=========

Replace the calls with the suggestions outlined above.
