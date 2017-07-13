.. include:: ../../Includes.txt

=========================================================================================
Important: #81899 - Backend AJAX routes use "&route=/ajax/" instead of "ajaxId" parameter
=========================================================================================

See :issue:`81899`

Description
===========

The TYPO3 Backend uses AJAX calls by calling routes with the ``&route=/ajax/*`` GET/POST parameter
now instead of the "&ajaxId" GET/POST parameter.

Although this is not a breaking change, some PHP code might rely on GET/POST parameters being
set, and must check for the route parameter instead.

.. index:: Backend