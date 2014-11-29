===========================================================
Feature: #61577 - Backend markup for checkboxes with labels
===========================================================

Description
===========

A typical checkbox with label form element should now be rendered as:

::

<div class="checkbox">
	<label for="someId">
		<input type="checkbox" id="someId" />
		Label text
	</label>
</div>
..

Impact
======

If this HTML markup is applied, CSS styles by the TYPO3 core will take care of optimized view
and custom CSS has become obsolete.