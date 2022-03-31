.. include:: /Includes.rst.txt

======================================================================
Feature: #79250 - EXT:form extend the extension location functionality
======================================================================

See :issue:`79250`

Description
===========

EXT:form has a feature to load custom form definitions from within extension locations.
These locations can be configured through the :code:`allowedExtensionPaths` setting.
To define whether forms can be changed from within extension locations through the form editor, a setting named :code:`allowSaveToExtensionPaths` exists.
But this setting affects only already existing form definitions within extension locations.
This feature makes it possible to store new forms within extension locations through the form manager as well.
You can also define whether forms can be deleted within extension locations through the form manager with a new setting called :code:`allowDeleteFromExtensionPaths`.
By default both settings :code:`allowSaveToExtensionPaths` and :code:`allowDeleteFromExtensionPaths` are disabled.


Summary
=======

With this patch is it possible to:

* save existing forms within extension locations ("allowedExtensionPaths") if "allowSaveToExtensionPaths" is set to true (like before)
* save new created forms within extension locations ("allowedExtensionPaths") if "allowSaveToExtensionPaths" is set to true
* delete forms within extension locations ("allowedExtensionPaths") if "allowDeleteFromExtensionPaths" is set to true


Impact
======

Example to allow edit form definitions within 'EXT:my_ext/Resources/Private/Forms/':

.. code-block:: yaml

 TYPO3:
      CMS:
        Form:
          persistenceManager:
            allowSaveToExtensionPaths: true
            allowedExtensionPaths:
              100: EXT:my_ext/Resources/Private/Forms/


Example to allow remove form definitions within 'EXT:my_ext/Resources/Private/Forms/':

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          persistenceManager:
            allowDeleteFromExtensionPaths: true
            allowedExtensionPaths:
              100: EXT:my_ext/Resources/Private/Forms/


.. index:: Backend, ext:form
