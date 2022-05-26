.. include:: /Includes.rst.txt

.. _users-manual:

============
Users manual
============

The felogin extension requires no special configuration. All options
are available in the plugin's FlexForm as shown in the :ref:`screenshots`.


.. _using-plugin:

Using the plugin
================

The felogin plugin is available through the Content Wizard as :guilabel:`Login Form`:


.. figure:: ../Images/ContentElementWizard.png
   :alt: The content element wizard

   The Login Form plugin in the content element wizard


.. _storage-folder:

Choosing a User Storage Page for Website Users
==============================================

In order for Website Users to be able to log in, the felogin plugin
must know where the records are stored. There are two possibilities
for setting this storage folder:

- Edit the felogin plugin, setting the field for the :guilabel:`User Storage
  Page` to your storage page.

- Or set the UID of you storage folder through TypoScript in the setup
  field of your TypoScript Template:

.. code-block:: typoscript

   plugin.tx_felogin_login.settings.pages = xxx

