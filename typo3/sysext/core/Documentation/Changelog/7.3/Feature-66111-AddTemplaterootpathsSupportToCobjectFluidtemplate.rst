
.. include:: /Includes.rst.txt

========================================================================
Feature: #66111 - Add TemplateRootPaths support to cObject FLUIDTEMPLATE
========================================================================

See :issue:`66111`

Description
===========

cObject FLUIDTEMPLATE has been extended with `templateRootPaths` and `templateName`. Now you can set a template name
and when rendering the template this name is used together with the set format to find the template in the given
templateRootPaths with the same fallback logic as layoutRootPath and partialRootPath

- templateName = string/stdWrap
- templateRootPaths = array of file paths with "EXT:" prefix support


Example 1:
----------

.. code-block:: typoscript

	lib.stdContent = FLUIDTEMPLATE
	lib.stdContent {
		templateName = Default
		layoutRootPaths {
			10 = EXT:frontend/Resources/Private/Layouts
			20 = EXT:sitemodification/Resources/Private/Layouts
		}
		partialRootPaths {
			10 = EXT:frontend/Resources/Private/Partials
			20 = EXT:sitemodification/Resources/Private/Partials
		}
		templateRootPaths {
			10 = EXT:frontend/Resources/Private/Templates
			20 = EXT:sitemodification/Resources/Private/Templates
		}
		variable {
			foo = bar
		}
	}

Example 2:
----------

.. code-block:: typoscript

	lib.stdContent = FLUIDTEMPLATE
	lib.stdContent {

		templateName = TEXT
		templateName.stdWrap {
			cObject = TEXT
			cObject {
				data = levelfield:-2,backend_layout_next_level,slide
				override.field = backend_layout
				split {
					token = frontend__
					1.current = 1
					1.wrap = |
				}
			}
			ifEmpty = Default
		}
		layoutRootPaths {
			10 = EXT:frontend/Resources/Private/Layouts
			20 = EXT:sitemodification/Resources/Private/Layouts
		}
		partialRootPaths {
			10 = EXT:frontend/Resources/Private/Partials
			20 = EXT:sitemodification/Resources/Private/Partials
		}
		templateRootPaths {
			10 = EXT:frontend/Resources/Private/Templates
			20 = EXT:sitemodification/Resources/Private/Templates
		}
		variable {
			foo = bar
		}
	}


Impact
======

If templateName and templateRootPaths are set the template and file options are neglected.


.. index:: TypoScript, Fluid, Frontend
