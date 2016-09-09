
.. include:: ../../Includes.txt

=========================================================================================
Breaking: #67890 - Redesign FluidTemplateDataProcessorInterface to DataProcessorInterface
=========================================================================================

See :issue:`67890`

Description
===========

The `FluidTemplateDataProcessorInterface` introduced with #66907 has been refactored to `DataProcessorInterface`.

This decouples it from the Fluid StandaloneView and makes the ContentObjectRenderer available in the process method
so the different DataProcessor classes do no have to initiate it on their own.
Instead of manipulating the `$data` property of the `ContentObjectRenderer` a new key/value store can be filled/manipulated
by the different dataProcessor classes.

The new interface expects the following `process()` method:

.. code-block:: php

	/**
	 * Process content object data
	 *
	 * @param ContentObjectRenderer $cObj The data of the content element or page
	 * @param array $processorConfiguration The configuration of this processor
	 * @param array $contentObjectConfiguration The configuration of Content Object
	 * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
	 * @return array the processed data as key/value store
	 */
	public function process(
		ContentObjectRenderer $cObj,
		array $contentObjectConfiguration,
		array $processorConfiguration,
		array $processedData
	);


Impact
======

This will break all frontend rendering for TYPO3 7.3 installations that use `FLUIDTEMPLATE` `.dataProcessing`.


Affected Installations
======================

All TYPO3 7.3 installations that already use the new `FLUIDTEMPLATE` `.dataProcessing` option.


Migration
=========

Change the interface of all DataProcessor classes from `FluidTemplateDataProcessorInterface` to the new
`DataProcessorInterface` and adjust the `process()` method to match the new parameters and make sure it returns the
processed data as the processed data.
