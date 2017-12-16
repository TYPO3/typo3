
.. include:: ../../Includes.txt

==================================================================
Feature: #70170 - ViewHelper to strip whitespace between HTML tags
==================================================================

See :issue:`70170`

Description
===========

Removes redundant spaces between HTML tags while preserving the whitespace that may be inside HTML tags. Trims the final result before output.

Heavily inspired by Twig's corresponding node type.

.. code-block:: html

	<code title="Usage of f:spaceless">
	<f:spaceless>
	<div>
	    <div>
	        <div>text

	text</div>
	</div>
	</div>
	</f:spaceless>
	</code>
	<output>
	<div><div><div>text

	text</div></div></div>
	</output>
