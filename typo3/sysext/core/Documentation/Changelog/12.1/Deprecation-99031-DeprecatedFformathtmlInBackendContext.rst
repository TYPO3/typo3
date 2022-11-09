.. include:: /Includes.rst.txt

.. _deprecation-99031-1667998430:

=================================================================
Deprecation: #99031 - Deprecated f:format.html in Backend context
=================================================================

See :issue:`99031`

Description
===========

The :html:`<f:format.html />` ViewHelper :php:`TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper`
should not be used in TYPO3 Backend context anymore.

Using this ViewHelper in Backend context triggers Frontend :typoscript:`parseFunc` logic, which
should be avoided in the backend.

There are other ViewHelpers to output and parse HTML in Backend context. See description of
the :ref:`f:sanitize.html <feature-94825-1667998632>` ViewHelper for more details.


Impact
======

Using :html:`<f:format.html />` logs a deprecation level warning.


Affected installations
======================

Instances with extensions that come with Backend modules using Fluid rendering and
accessing :html:`<f:format.html />` are affected.


Migration
=========

Switch to one of the other ViewHelpers instead, typically :html:`<f:sanitize.html />`
to secure a given HTML string, :html:`<f:transform.html />` to parse links in HTML,
or :html:`<f:format.raw />` to output the HTML as is when the input can be considered
"secure".


.. index:: Backend, Fluid, NotScanned, ext:fluid
