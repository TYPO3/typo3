..  include:: /Includes.rst.txt

..  _breaking-107831-1761307521:

========================================================
Breaking: #107831 - Removed TypoScriptFrontendController
========================================================

See :issue:`107831`

Description
===========

All remaining properties have been removed from :php:`TypoScriptFrontendController`,
making the class a readonly service, used TYPO3 core internally only. The class
will vanish during further TYPO3 v14 development.

The following instance access patterns have been removed:

.. code-block:: php

    $GLOBALS['TSFE']
    $request->getAttribute('frontend.controller')
    AbstractContentObject->:getTypoScriptFrontendController()

All API method that returned an instance of :php:`TypoScriptFrontendController`,
usually called :php:`getTypoScriptFrontendController` or similar.

Impact
======

Remaining direct and indirect usages of :php:`TypoScriptFrontendController` will fail.


Affected installations
======================

Some extensions may still have used details of :php:`TypoScriptFrontendController`
directly, even though the class has been marked breaking and internal with
TYPO3 v13 already.

In particular, extensions that utilized :php:`AbstractContentObject->getTypoScriptFrontendController()`
can now access relevant parts from the request object, e.g.
:php:`$request->getAttribute('frontend.page.information')`.


Migration
=========

See :ref:`breaking-102621-1701937690` for a list of old properties and their
substitutions.

One last and not yet mentioned detail, old code:

.. code-block:: php

    $request->getAttribute('frontend.controller')->additionalHeaderData[] = $myAdditionalHeaderData;

New code:

.. code-block:: php

    GeneralUtility::makeInstance(PageRenderer::class)->addHeaderData($myAdditionalHeaderData);

The same strategy can be used for :php:`additionalFooterData`.

..  index:: Frontend, NotScanned, ext:frontend
