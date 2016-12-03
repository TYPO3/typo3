.. include:: ../../Includes.txt

=============================================
Breaking: #78899 - Dropped FormEngine Methods
=============================================

See :issue:`78899`

Description
===========

The following methods have been dropped:

* :code:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->dbFileIcons()`
* :code:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->getClipboardElements()`

The following properties have been dropped:

* :code:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->clipboard`

The following hook interface has been dropped and registered hooks in :code:`dbFileIcons` are no longer called:

* :code:`TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface`


Impact
======

Using above methods, properties and hooks will result in fatal :code:`PHP` errors or fail silently.


Affected Installations
======================

Check extensions for usages of above methods and especially implementations of the hook interface.


Migration
=========

The methods have been partially moved to the :code:`TcaGroup` data provider and merged to the two
FormEngine elements :code:`GroupEleement` and :code:`SelectMulitpleSideBySideElement`. Those can be
changed and extended via the FormEngine internal :code:`NodeFactory` and data provider resolvers.

.. index:: Backend, PHP-API