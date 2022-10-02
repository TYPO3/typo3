.. include:: /Includes.rst.txt

.. _breaking-96708:

==========================================================
Breaking: #96708 - Removed support for accesskeys in HMENU
==========================================================

See :issue:`96708`

Description
===========

TYPO3's built-in support for menu generation, adding :html:`accesskey`
HTML attributes to menu items has been removed.

As stated by various sources such as

* https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/accesskey#accessibility_concerns
* https://webaim.org/standards/wcag/checklist#:~:text=accesskey%20should%20typically%20be%20avoided

this feature should only be used by explicitly defining access keys when
a use-case is given.

TYPO3 menus previously used a random link title as an access key, when the
TypoScript property :typoscript:`HMENU.accessKey = 1` was set.

Along with the accessKey functionality, the public property
:php:`TypoScriptFrontendController->accessKey` has been removed.

Impact
======

Setting the TypoScript option has no effect anymore.

Accessing the removed public property will trigger a PHP warning. The
extension scanner will detect usages as weak match.

Affected Installations
======================

TYPO3 installations using the :typoscript:`accessKey` feature of HMENU or
accessing the :php:`accessKey` property of :php:`TypoScriptFrontendController`.

TYPO3 installations using the global :html:`accesskey` HTML attribute in
their own code will still work as before.

Migration
=========

Using the :html:`accesskey` HTML attribute should be avoided in general, but
if needed, integrators should add it to their templates in a sensible way,
depending on the accessibility needs.

.. index:: Frontend, TypoScript, PartiallyScanned, ext:frontend
