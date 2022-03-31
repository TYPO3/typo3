.. include:: /Includes.rst.txt

=================================================================
Important: #88043 - TypeScript sources moved into Build directory
=================================================================

See :issue:`88043`

Description
===========

The TypeScript sources of all system extensions have been moved into the :file:`Build` directory. The former directory
structure :file:`typo3/sysext/foobar/Resources/Private/TypeScript` has been superseded by the new structure
:file:`Build/Sources/TypeScript/foobar/Resources/Public/TypeScript`.

.. note::

   Mind that :file:`Public` is now the parent directory of :file:`TypeScript`.

.. index:: JavaScript
