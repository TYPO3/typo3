
.. include:: /Includes.rst.txt

===============================================
Feature: #46624 - Additional HMENU browse menus
===============================================

See :issue:`46624`

Description
===========

The TypoScript Content Object HMENU with a `special=browse` option does not exclude "not in menu" pages nor
pages that have a "no search" checkbox set. The two new options allow for more fine-grained selection of the items
within the menu.

The existing option "includeNotInMenu" was not available yet for the `HMENU` with `special=browse` enabled.

.. code-block:: typoscript

	lib.browsemenu = HMENU
	lib.browsemenu.special = browse
	lib.browsemenu.special.excludeNoSearchPages = 1
	lib.browsemenu.includeNotInMenu = 1
	...


.. index:: TypoScript, Frontend
