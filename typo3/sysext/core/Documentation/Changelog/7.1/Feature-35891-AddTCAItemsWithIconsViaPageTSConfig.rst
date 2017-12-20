
.. include:: ../../Includes.txt

=======================================================================
Feature: #35891 - FormEngine: Possibility to add icons via PageTSconfig
=======================================================================

See :issue:`35891`

Description
===========

The possibility to add a new value/label pair for a select field in FormEngine is given via the pageTSconfig option
"addItems". Now, it is also possible to give the items an icon. Either with the .icon subproperty or with the
separate option "altIcons".

.. code-block:: typoscript

	TCEFORM.pages.doktype.addItems {
		13 = My Label
		13.icon = EXT:t3skin/icons/gfx/i/pages.gif
	}
	TCEFORM.pages.doktype.altIcons {
		123 = EXT:myext/icon.gif
	}

If the path is not prefixed with "EXT:" it needs to be relative to the typo3/ directory.


.. index:: TSConfig, TCA, Backend
