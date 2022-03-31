
.. include:: /Includes.rst.txt

==============================================
Breaking: #67229 - FormEngine related classses
==============================================

See :issue:`67229`


Description
===========

With the further development of FormEngine, some minor changes on PHP level have been applied:

* Class `TYPO3\CMS\T3editor\FormWizard` has been removed

* Class `TYPO3\CMS\Rtehtmlarea\Controller\FrontendRteController` has been removed

* The method signature of class `TYPO3\CMS\Utility\BackendUtility` method `getSpecConfParts` has changed


Impact
======

Using code will fatal or not be called any longer.


Affected Installations
======================

If extensions use above classes or methods. Since these classes are mostly core internal
it is quite unlikely any project in the wild is affected.


Migration
=========

Use the newly introduced API.


.. index:: PHP-API, Backend, ext:t3editor, RTE
