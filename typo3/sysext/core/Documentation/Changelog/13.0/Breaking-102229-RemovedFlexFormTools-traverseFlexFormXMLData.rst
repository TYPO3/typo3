.. include:: /Includes.rst.txt

.. _breaking-102229-1698053674:

====================================================================
Breaking: #102229 - Removed FlexFormTools->traverseFlexFormXMLData()
====================================================================

See :issue:`102229`

Description
===========

Class :php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` got a series
of cleanup and removal patches.

The following public class properties have been removed:

* :php:`$reNumberIndexesOfSectionData`
* :php:`$flexArray2Xml_options`
* :php:`$callBackObj`
* :php:`$cleanFlexFormXML`

The following public methods have been removed:

* :php:`traverseFlexFormXMLData()`
* :php:`traverseFlexFormXMLData_recurse()`
* :php:`cleanFlexFormXML_callBackFunction()`

The following public methods have been marked `@internal`:

* :php:`cleanFlexFormXML()`
* :php:`flexArray2Xml()`
* :php:`migrateFlexFormTcaRecursive()`

The class is now a stateless service and can be injected as shared service
without any risk of triggering side effects.


Impact
======

In general, these changes should have relatively low impact on extensions, if they
don't build additional low level functionality on top of the general TYPO3 Core
FlexForm related features. Extensions like the TemplaVoila forks may need to have
a look for required adaptions, though.

Using the removed methods or properties in TYPO3 v13 will of course trigger PHP
fatal errors.


Affected installations
======================

Instances that extend functionality of FlexForm handling may be affected if they
use methods of class :php:`FlexFormTools`. This is a relatively rare case, most
instances will not be affected when they just provide and use casual FlexForm
definitions in extensions.

The extension scanner will find possible extensions that consume the methods or
properties as a weak match.


Migration
=========

If at all, method :php:`traverseFlexFormXMLData()` is probably the one used in
extensions. The easiest way is to copy the method and it's recursive worker method
to an own class.

Extension developers are however encouraged to refactor their code since
:php:`traverseFlexFormXMLData()` with its callback logic was ugly, hard to follow
and maintain. The Core switched away from the method by implementing own traversers
that match the specific use cases. Method :php:`cleanFlexFormXML()` is an
example of such an implementation. Note FlexForms are *not* recursive since
section containers can not be nested since TYPO3 v8 anymore. The Core thus
uses some nested foreach loops instead of a recursive approach.


.. index:: FlexForm, PHP-API, FullyScanned, ext:core
