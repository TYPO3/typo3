
.. include:: ../../Includes.txt

===============================================================
Feature: #67658 - Introduce DataProcessors for splitting values
===============================================================

See :issue:`67658`

Description
===========

Two new DataProcessors are added to allow flexible processing for comma-separated
values. To use e.g. with the FLUIDTEMPLATE content object.

The SplitProcessor allows to split values separated with a delimiter inside a single database field
into an array to loop over it.

The CommaSeparatedValueProcessor allows to split values into a two-dimensional array used for
CSV files or tt_content records of CType "table".

Using the SplitProcessor the following scenario is possible:

.. code-block:: typoscript

	page.10 = FLUIDTEMPLATE
	page.10.file = EXT:site_default/Resources/Private/Template/Default.html
	page.10.dataProcessing.2 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
	page.10.dataProcessing.2 {
		if.isTrue.field = bodytext
		delimiter = ,
		fieldName = bodytext
		removeEmptyEntries = 1
		filterIntegers = 1
		filterUnique = 1
		as = keywords
	}


In the Fluid template then iterate over the split data:

.. code-block:: html

	<f:for each="{keywords}" as="keyword">
		<li>Keyword: {keyword}</li>
	</f:for>


Using the CommaSeparatedValueProcessor the following scenario is possible:

.. code-block:: typoscript

	page.10 = FLUIDTEMPLATE
	page.10.file = EXT:site_default/Resources/Private/Template/Default.html
	page.10.dataProcessing.4 = TYPO3\CMS\Frontend\DataProcessing\CommaSeparatedValueProcessor
	page.10.dataProcessing.4 {
		if.isTrue.field = bodytext
		fieldName = bodytext
		fieldDelimiter = |
		fieldEnclosure =
		maximumColumns = 2
		as = table
	}


In the Fluid template then iterate over the processed data:

.. code-block:: html

	<table>
		<f:for each="{table}" as="columns">
			<tr>
				<f:for each="{columns}" as="column">
					<td>{column}</td>
				</f:for>
			<tr>
		</f:for>
	</table>
