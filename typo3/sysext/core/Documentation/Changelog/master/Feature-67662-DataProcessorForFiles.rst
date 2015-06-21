=========================================
Feature: #67662 - DataProcessor for files
=========================================

Description
===========

A new Files DataProcessor has been introduced, which can be used to prepare data to be handled by a ContentObject
implementing the processors, e.g. the FLUIDTEMPLATE ContentObject. The FilesProcessor resolves File References, Files,
or Files inside a folder or collection to be used for output in the Frontend. A FLUIDTEMPLATE can then simply iterate
over processed data automatically.


.. code-block:: typoscript

	tt_content.image.20 = FLUIDTEMPLATE
	tt_content.image.20.file = EXT:myextension/Resources/Private/Templates/ContentObjects/Image.html
	tt_content.image.20.dataProcessing.10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor

	# the field name where relations are set
	# + stdWrap
	tt_content.image.20.dataProcessing.10.references.fieldName = image

	# the table name where relations are put, defaults to the currently selected record from $cObj->getTable()
	# + stdWrap
	tt_content.image.20.dataProcessing.10.references.table = tt_content

	# A list of sys_file UID records
	# + stdWrap
	tt_content.image.20.dataProcessing.10.files = 21,42

	# A list of File Collection UID records
	# + stdWrap
	tt_content.image.20.dataProcessing.10.collections = 13,14

	# A list of FAL Folder identifiers
	# + stdWrap
	tt_content.image.20.dataProcessing.10.folders = 1:introduction/images/,1:introduction/posters/

	# Property of which the files should be sorted after they have been accumulated + stdWrap
	# can be any property of sys_file, sys_file_metadata
	tt_content.image.20.dataProcessing.10.sorting = description

	# Can be "ascending", "descending" or "random", defaults to "ascending" if none given + stdWrap
	tt_content.image.20.dataProcessing.10.sorting.direction = descending

	# The target variable to be handed to the ContentObject again, can be used
	# in Fluid e.g. to iterate over the objects. defaults to "files" when non given
	# + stdWrap
	tt_content.image.20.dataProcessing.10.as = myfiles


In the Fluid template then iterate over the files:

.. code-block:: html
	<ul>
	<f:for each="{myfiles}" as="file">
		<li><a href="{file.publicUrl}">{file.name}</a></li>
	</f:for>
	</ul>
