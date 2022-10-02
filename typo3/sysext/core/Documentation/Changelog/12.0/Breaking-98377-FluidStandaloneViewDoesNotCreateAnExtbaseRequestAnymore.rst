.. include:: /Includes.rst.txt

.. _breaking-98377-1663607123:

==================================================================================
Breaking: #98377 - Fluid StandaloneView does not create an Extbase Request anymore
==================================================================================

See :issue:`98377`

Description
===========

In our efforts to further speed up, streamline and separate Fluid from Extbase,
the :php:`\TYPO3\CMS\Fluid\View\StandaloneView` has been changed to no longer
create a Extbase Request anymore.

StandaloneView is typically not used in Extbase context, creating an Extbase
Request at this point was a very unfortunate architectural flaw leading to a
not wanted context switch.

Not having an Extbase Request within StandaloneView anymore can have impact on
behavior of some Fluid ViewHelpers.

Impact
======

Common usages of StandaloneView are a frontend related :typoscript:`FLUIDTEMPLATE`
content object, plus various usages in non-Extbase extensions like rendering eMails
or similar. Within :typoscript:`FLUIDTEMPLATE`, the current non-Extbase PSR-7
ServerRequest is actively set to StandaloneView, custom extension usages may need
to :php:`$view->setRequest($request)` explicitly.

Some ViewHelpers that rely on Extbase functionality throw exceptions when
a Request is not set, or if the Request is not an Extbase Request. Those will
refuse to work for instance when used in a template triggered by a :typoscript:`FLUIDTEMPLATE`
content object.

Most notably, all :html:`f:form` ViewHelpers are affected of this, plus
eventually custom ViewHelpers that access Extbase specific :php:`Request`
methods.

Affected installations
======================

Instances with extensions using StandaloneView in their code may need attention,
and frontend rendering using :typoscript:`FLUIDTEMPLATE` content objects may need
adaptions if Extbase-only ViewHelpers like :html:`f:form` are used.

Migration
=========

Avoiding :html:`f:form` in non-Extbase context
----------------------------------------------

The :html:`f:form` ViewHelpers are Extbase specific: They especially take care of
handling Extbase internal fields like :html:`__referrer` and similar. The casual solution
is to switch these usages away from those ViewHelpers, and use the HTML counterparts
directly, for instance using :html:`<input ...>` instead of :html:`<f:form.input ...>`.

Custom StandaloneView code
--------------------------

Extensions that instantiate :php:`StandaloneView` may want to :php:`$view->setRequest($request)`
to hand over the current request to the view, since the request is no longer initialized
automatically. This is needed for ViewHelpers that rely on :php:`$renderingContext->getRequest()`.

Custom ViewHelpers
------------------

Custom ViewHelpers used in :php:`StandaloneView` that call methods from Extbase
:php:`TYPO3\CMS\Extbase\Mvc\Request` which are not part of
:php:`Psr\Http\Message\ServerRequestInterface` will throw fatal PHP errors.

Possible solutions:

* Create an Extbase Request within a controller and :php:`setRequest()` it to the
  view instance as quick solution.
* Properly boot Extbase using the Extbase Bootstrap to have a fully initialized
  Extbase Request in the View.
* Avoid using Extbase specific methods within the ViewHelper by checking if the
  incoming request implements Extbase :php:`TYPO3\CMS\Extbase\Mvc\RequestInterface`.
  This allows creating "hybrid" ViewHelpers that work in both contexts.
* Avoid using Extbase specific methods within the ViewHelper by fetching data from
  the given :php:`Psr\Http\Message\ServerRequestInterface` Request, or it's attached
  core attributes.

.. index:: Fluid, PHP-API, NotScanned, ext:fluid
