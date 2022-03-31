
.. include:: /Includes.rst.txt

===========================================================
Feature: #61577 - Backend markup for checkboxes with labels
===========================================================

See :issue:`61577`

Description
===========

A typical checkbox with label form element should now be rendered as:

.. code-block:: html

	<div class="checkbox">
		<label for="someId">
			<input type="checkbox" id="someId" />
			Label text
		</label>
	</div>


Impact
======

If this HTML markup is applied, CSS styles by the TYPO3 core will take care of optimized view
and custom CSS has become obsolete.


.. index:: Backend
