
.. include:: /Includes.rst.txt

==============================================
Feature: #68094 - Database Query DataProcessor
==============================================

See :issue:`68094`

Description
===========

A new Database Query DataProcessor has been introduced, which can be used to fetch data from the Database
to be handled by a ContentObject implementing the processors, e.g. the FLUIDTEMPLATE ContentObject.

The Database Query Processor works like the code from the Content Object CONTENT, except for just handing
over the result as array. A FLUIDTEMPLATE can then simply iterate over processed data automatically.

.. code-block:: typoscript

	tt_content.mycontent.20 = FLUIDTEMPLATE
	tt_content.mycontent.20 {
		file = EXT:myextension/Resources/Private/Templates/ContentObjects/MyContent.html

		dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\DatabaseQueryProcessor
		dataProcessing.10 {
			# regular if syntax
			if.isTrue.field = records

			# the table name from which the data is fetched from
			# + stdWrap
			table = tt_address

			# All properties from .select can be used directly
			# + stdWrap
			colPos = 1
			pidInList = 13,14

			# The target variable to be handed to the ContentObject again, can be used
			# in Fluid e.g. to iterate over the objects. defaults to "records" when not defined
			# + stdWrap
			as = myrecords

			# The fetched records can also be processed by DataProcessors.
			# All configured processors are applied to every row of the result.
			dataProcessing {
				10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
				10 {
					references.fieldName = image
				}
			}
		}
	}

In the Fluid template then iterate over the files:

.. code-block:: html

	<ul>
	<f:for each="{myrecords}" as="record">
		<li>
			<f:image image="{record.files.0}" />
			<a href="{record.data.www}">{record.data.first_name} {record.data.last_name}</a>
		</li>
	</f:for>
	</ul>


.. index:: Frontend, Fluid
