.. include:: ../../Includes.txt

=================================================
Important: #79221 - Use $ instead of TYPO3.jQuery
=================================================

See :issue:`79221`

Description
===========

The TYPO3 Core uses jQuery in the TYPO3 Backend with a default namespace of :javascript:`TYPO3.jQuery` and :javascript:`jQuery` in the global object
namespace, and is now also available as global `$` when no other namespace is given. This was not possible before due to conflicts with
prototype.js.

If using the shipped jQuery code in the Frontend explicitly via :ts:`page.javascriptLibs.jQuery.noConflict.namespace = default` then the
global :javascript:`$` is also available in frontend scripts.

.. index:: JavaScript, TypoScript
