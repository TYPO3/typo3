
.. include:: ../../Includes.txt

=========================================================================
Feature: #45725 - Added recursive option to folder based file collections
=========================================================================

See :issue:`45725`

Description
===========

Folder based file collections have now an option to fetch all files recursively for
the given folder. The option is also available in the TypoScript Object `FILES`.

Usage:

.. code-block:: typoscript

	filecollection = FILES
	filecollection {
		folders = 1:images/
		folders.recursive = 1

		renderObj = IMAGE
		renderObj {
			file.import.data = file:current:uid
		}
	}


.. index:: TypoScript, Frontend, FAL
