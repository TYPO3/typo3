
.. include:: ../../Includes.txt

=========================================================
Feature: #61903 - PageTS dataprovider for backend layouts
=========================================================

See :issue:`61903`

Description
===========

Over the last year, several extensions appeared on TER that implemented the very same basic feature:
Deploying backend layouts without database records by providing them via PageTS.

Implement a generic PageTS provider for backend layouts to unify those approaches and to make backend layouts reusable
across installations.


Impact
======

It is now possible to define backend layouts via PageTSConfig on every page.


Example
-------

.. code-block:: typoscript

	mod {
		web_layout {
			BackendLayouts {
				exampleKey {
					title = Example
					config {
						backend_layout {
							colCount = 1
							rowCount = 2
							rows {
								1 {
									columns {
										1 {
											name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3
											colPos = 3
											colspan = 1
										}
									}
								}
								2 {
									columns {
										1 {
											name = Main
											colPos = 0
											colspan = 1
										}
									}
								}
							}
						}
					}
					icon = EXT:example_extension/Resources/Public/Images/BackendLayouts/default.gif
				}
			}
		}
	}


.. index:: TSConfig, Backend
