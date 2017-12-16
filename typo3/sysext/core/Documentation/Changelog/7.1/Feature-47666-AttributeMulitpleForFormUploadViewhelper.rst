
.. include:: ../../Includes.txt

=====================================================================
Feature: #47666 - Attribute \"multiple\" for f:form.upload Viewhelper
=====================================================================

See :issue:`47666`

Description
===========

The Viewhelper allows now an attribute \"multiple\", that will provide
a possibility to upload several files at once.

.. code-block:: html

	<f:form.upload property="files" multiple="multiple" />

Will result in the according HTML tag providing the field content as array.

Be aware, that you need to prepare the incoming value for the property mapping yourself,
by writing your own TypeConverter.
