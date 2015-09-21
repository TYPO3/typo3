.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.


.. _start:

=============
Documentation
=============

This extension provides a Backend module for TYPO3 to show both the documentation of local extensions and custom
documents.

The Backend module features two actions:

#. Show Documentation
#. Download Documentation


Show Documentation
==================

This view shows a list of available documents:

- Extensions with a manual rendered as ``html`` or ``pdf``;
- Extensions with an OpenOffice manual (``sxw``);
- Official TYPO3 documentation (tutorials, references, ...) available locally;
- Custom documents, rendered either as ``html`` or ``pdf``.

To be listed, documents should be stored within ``typo3conf/Documentation/<documentation-key>/<language>/<format>/``:

``documentation-key``
	Extensions use the documentation key ``typo3cms.extensions.<extension-key>``.

``language``
	Either "default" (for English) or a proper locale identifying your translated documentation. E.g.,
	``fr_FR``, ``fr_CA``, ``de_DE`` ...

``format``
	Either ``html`` or ``pdf``. Additional formats may be supported by 3rd party extensions
	(such as `EXT:sphinx <https://typo3.org/extensions/repository/view/sphinx>`_).


Registering Custom Documents
----------------------------

#. Choose a documentation key such as ``<company>.<document-name>``

#. Put your documentation as HTML (main file *must be* ``Index.html``) within
   ``typo3conf/Documentation/<documentation-key>/default/html/`` or as PDF (any name will fit) within
   ``typo3conf/Documentation/<documentation-key>/default/pdf/``

#. Create a text description file ``composer.json`` containing the title and description of your documentation and place
   it within ``typo3conf/Documentation/<documentation-key>/default/``:

   .. code-block:: json

       {
           "name": "Put some title here",
           "type": "documentation",
           "description": "Put some description here."
       }

#. [optionally] Put a custom icon (either ``icon.png`` or ``icon.gif``) within directory
   ``typo3conf/Documentation/<documentation-key>/``


Download Documentation
======================

This view is only accessible to TYPO3 administrators. It shows a form to retrieve rendered documentation for loaded
extensions and to fetch a copy of official TYPO3 manuals, guides and references from https://docs.typo3.org.


Configuration
=============

There are two User TSconfig options available:

mod.help_DocumentationDocumentation.documents.hide
  Comma-separated list of keys of documentation that should be hidden from the user.

mod.help_DocumentationDocumentation.documents.show
  Comma-separated list of keys of documentation that should be shown to the user (others are implicitly hidden).
