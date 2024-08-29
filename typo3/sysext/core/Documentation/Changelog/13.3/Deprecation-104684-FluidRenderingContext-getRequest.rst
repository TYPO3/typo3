.. include:: /Includes.rst.txt

.. _deprecation-104684-1724258020:

===========================================================
Deprecation: #104684 - Fluid RenderingContext->getRequest()
===========================================================

See :issue:`104684`

Description
===========

The following methods have been marked as deprecated in TYPO3 v13 and will
be removed with TYPO3 v14:

* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setRequest()`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getRequest()`


Impact
======

Calling above methods triggers a deprecation level log entry in TYPO3 v13 and
will trigger a fatal PHP error with TYPO3 v14.


Affected installations
======================

:php:`RenderingContext->getRequest()` is a relatively common call in custom
view helpers. Instances with extensions that deliver custom view helpers may
be affected. The extension scanner is *not* configured to find potential
places since the method names are common and would lead to too many false
positives.


Migration
=========

Class :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext` of the core
extension fluid extends class :php:`TYPO3Fluid\Fluid\Core\Rendering\RenderingContext`
of fluid standalone and adds the methods :php:`setRequest()` and :php:`getRequest()`.
These methods are however not part of :php:`TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface`.

Fluid standalone will not add these methods, since the view of this library should
stay free from direct PSR-7 :php:`ServerRequestInterface` dependencies. Having those
methods in ext:fluid :php:`RenderingContext` however collides with :php:`RenderingContextInterface`,
which is type hinted in fluid view helper method signatures.

Fluid standalone instead added three methods to handle arbitrary additional data
in :php:`RenderingContextInterface`: :php:`setAttribute()`, :php:`hasAttribute()`
and :php:`getAttribute()`. Those should be used instead.

A typical usage in a view helper before:

.. code-block:: php

    /** @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext $renderingContext */
    $renderingContext = $this->renderingContext;
    $request = $renderingContext->getRequest();

After:

.. code-block:: php

    $request = null;
    if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
    }

To stay compatible to previous TYPO3 versions while avoiding deprecation notices,
the following code can be used:

.. code-block:: php

    if (
        method_exists($renderingContext, 'getAttribute') &&
        method_exists($renderingContext, 'hasAttribute') &&
        $renderingContext->hasAttribute(ServerRequestInterface::class)
    ) {
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
    } else {
        $request = $renderingContext->getRequest();
    }

.. index:: Fluid, PHP-API, NotScanned, ext:fluid
