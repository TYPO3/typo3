..  include:: /Includes.rst.txt

..  _breaking-107507-1758376090:

========================================================================================
Breaking: #107507 - Removed ext:form AbstractFinisher->getTypoScriptFrontendController()
========================================================================================

See :issue:`107507`

Description
===========

Method :php:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher->getTypoScriptFrontendController()`
has been removed. The entire class is being phased out, forcing removal of this abstract helper
method.


Impact
======

Calling the method in a custom ext:form finisher class will trigger a fatal PHP error.


Affected installations
======================

Instances using the form extension with custom finishers may be affected. The extension scanner is
configured to find usages.


Migration
=========

Migration depends on what is done with the class instance. Properties and helper methods
within :php:`TypoScriptFrontendController` are modeled differently, with most data being
available as request attributes.

An access to property :php:`cObj` can be substituted like this:

.. code-block:: php

    $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    $cObj->setRequest($request);
    $cObj->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');


..  index:: PHP-API, FullyScanned, ext:form
