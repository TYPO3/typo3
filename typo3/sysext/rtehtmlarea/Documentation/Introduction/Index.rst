.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _introduction:

Introduction
============


.. _what-does-it-do:

What does it do?
----------------

The extension offers a Rich Text Editor or RTE with the following
features:

- Support for Firefox 1.5+, SeaMonkey 1.0+, Safari 3.0.4+, Google Chrome
  1.0+ and Opera 9.62+ on all platforms, and for IE 9.0+ on Windows
  platforms;

- Integration of TYPO3 image insertion and link insertion browsers,
  configurable color selector and user element insertion dialog;

- Configuration through TYPO3 Extension Manager, Page and User TSconfig
  RTE properties; three default sets of Page and User TSconfig
  configuration settings for typical situations, advanced users or demo
  environments;

- Integration with the translation facilities of TYPO3;

- Block and inline CSS style selector boxes with style descriptors
  imported from an external CSS file;

- Integration of a spell checking feature providing server-side spell
  checking in many languages, with optional personal dictionaries for
  backend users;

- Integration of ContextMenu, TableOperations, InsertSmiley,
  FindReplace, RemoveFormat, CharacterMap, QuickTag and Acronym htmlArea
  extensions;

- Anchor accessibility feature;

- Clean paste feature;

- Hook on Lorem Ipsum wizard so that dummy content may be inserted when
  the editor is in wysiwyg mode;

- Optional configurable server-side HTML cleaning when content is pasted
  into the editor;

- A class that may be used in front end extensions to enable rich text
  editing of text fields.


.. _requirements:

Requirements
------------

If spell checker feature is enabled, then Static Info Tables version
2.0.0+ is required.

The spell checker feature requires `GNU Aspell 0.60+
<http://aspell.net/>`_ to be installed on the server.

The spell checker requires PHP to be compiled with pspell. If PHP is
not compiled with pspell, the spell checker will function in
shell\_exec mode.

The hook on the Lorem Ipsum wizard requires version 1.1.0+ of the
Lorem Ipsum extension (lorem\_ipsum).


.. _support:

Support
-------

Please see/report problems on TYPO3 Forge
`http://forge.typo3.org/projects/typo3v4-core/issues
<http://forge.typo3.org/projects/typo3v4-core/issues>`_ under category
rtehtmlarea.

You may get support in the use of this extension by subscribing to
`http://forum.typo3.org/index.php/f/50/
<http://forum.typo3.org/index.php/f/50/>`_ .


.. _what-s-new:

What's new
----------

The following features have been added in TYPO3 6.0:

- the RTE fully supports the new TYPO3 file abstraction layer (FAL);

- when Internet Explorer 9 is used in native mode, the RTE always uses
  the standard objects, properties and methods now supported by this
  browser;

- default paste behaviours for pasteStructure and pasteFormat have been
  modified to keep HTML5 tags article, aside, footer, header, nav and
  section; these defaults may be modified by configuring button
  pastebehaviour in Page TSconfig;

- Page TSconfig properties that were deprecated since TYPO3 4.6 are now
  removed.

