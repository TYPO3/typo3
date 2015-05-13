=====================================================================
Feature: #66907 - Add Data Processing to FLUIDTEMPLATE content object
=====================================================================

Description
===========

cObject FLUIDTEMPLATE is extended with ``dataProcessing``. This setting can be used to add one or multiple processors to
manipulate the ``$data`` variable of the currently rendered content object, like tt_content or page.

- dataProcessing = array of class references by full namespace


Example:
--------

.. code-block:: typoscript

	my_custom_ctype = FLUIDTEMPLATE
	my_custom_ctype {
		templateRootPaths {
			10 = EXT:your_extension_key/Resources/Private/Templates
		}
		templateName = CustomName
		settings {
			extraParam = 1
		}
		dataProcessing {
			1 = Vendor\YourExtensionKey\DataProcessing\MyFirstCustomProcessor
			2 = Vendor2\AnotherExtensionKey\DataProcessing\MySecondCustomProcessor
			2 {
				options {
					myOption = SomeValue
				}
			}
		}
	}


Impact
======

The data processors can be used in all new projects. There is no interference with any part of existing code.