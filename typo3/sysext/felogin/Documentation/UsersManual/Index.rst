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

Choosing a user storage page for website users
==============================================

In order for Website Users to be able to log in, the "Frontend login" plugin
must know where the records are stored. There are two possibilities
for setting this storage folder:

The site's integrator may have set a default value for the
:confval:`User Storage Page <felogin-felogin-pid>`. If you use the default
folder to store frontend users in your project there is nothing to do here.

If your project needs multiple storage folders for frontend users or
if there is no default storage folder set, see :ref:`Example: Override the
default storage page in the plugin's FlexForm <configuration-examples-flexform>`.

.. _access-restrictions:

Access restrictions on the felogin plugin
=========================================

A very common issue is, that the felogin plugin is set to Access:
:guilabel:`Hide at login`. After the core has processed the login request, the
page will be rendered without the felogin plugin. If there are redirect options
active they will **not be executed**, simply because the felogin plugin is
hidden.

Of course setting the felogin plugin to :guilabel:`Hide at login` and having
redirect options together doesn't really makes sense.

