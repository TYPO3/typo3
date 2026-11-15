..  include:: /Includes.rst.txt

..  _important-101833-1794756019:

====================================================================
Important: #101833 - Fileadmin temporary folder is no longer created
====================================================================

See :issue:`101833`

Description
===========

The folder structure check of the Install Tool and the "Installation"
status report no longer create the folder :file:`fileadmin/_temp_/` with
its :file:`.htaccess` and :file:`index.html` files. No code of the TYPO3
Core has used this folder for a long time.

Existing folders are kept. They can be deleted if no extension uses them.

The folder :file:`fileadmin/user_upload/_temp_/importexport/` is still
created and protected by an :file:`.htaccess` file, as the import and export
module of EXT:impexp uses it.

Extensions that store files in :file:`fileadmin/_temp_/` need to create the
folder themselves and protect it from public access, or use a location
outside of the public web folder.

..  index:: Backend, FAL, ext:install
