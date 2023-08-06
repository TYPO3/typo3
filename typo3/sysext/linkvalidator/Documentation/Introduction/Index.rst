.. include:: /Includes.rst.txt



.. _introduction:

Introduction
------------


.. _about-this-document:

About this document
^^^^^^^^^^^^^^^^^^^

The LinkValidator is provided by a system extension named :file:`linkvalidator`
which enables you to conveniently check your website for broken links.

This manual explains how to install and configure the extension for your needs.


.. _what-does-it-do:

What does it do?
^^^^^^^^^^^^^^^^

The LinkValidator checks the links in your website for validity, reports
broken links or missing files in your TYPO3 installation and provides
a way to conveniently fix these problems.

It includes the following features:

- The LinkValidator can check all kinds of links. This includes internal
  links to pages and content elements, file links to files in the local
  file system and external links to resources somewhere else in the web.

- The LinkValidator checks a number of fields by default, for example
  :sql:`header` and :sql:`bodytext` fields of content elements.
  It can be configured to check any field you like.

- The LinkValidator offers a just in time check of your website.
  Additionally the TYPO3 Scheduler is fully supported to run checks
  automatically. In this case you can choose, if you want to receive an
  email report, if broken links were found.

- The LinkValidator is extendable. It provides hooks to check special types
  of links or override how the checking of external, file and page
  links works.

.. _screenshots:

Screenshots
^^^^^^^^^^^

This is the :guilabel:`Check Links` backend module. It provides two actions:
:guilabel:`Report` and :guilabel:`Check Links`. The :guilabel:`Report` action
is always shown first.
Here you can view the broken links which were found, when your website was
last checked.

.. figure:: ../Images/ReportsTab.png
   :alt: The Reports action

   Viewing broken links in the :guilabel:`Report` action


The :guilabel:`Check Links` tab is used to check links on demand and can be
hidden with TSconfig, if desired.

.. figure:: ../Images/CheckLinksTab.png
   :alt: The Check links tab

   Checking links live in the TYPO3 Backend


The workflow in the module is the following:

-   First you set the depth of pages you want to consider when checking
    for broken links in the :guilabel:`Check Links` tab. Then click the
    :guilabel:`Check Links`
    button.

-   Once the checks are done, the module automatically switches to the
    :guilabel:`Report` tab where the results are displayed.

-   The type and ID of the content containing the broken link become
    visible when you move the mouse over the icon for the content type.
    The pencil icons at the beginning of each row enable you to quickly
    fix the displayed elements.

The LinkValidator features full support of the TYPO3 Scheduler. This is
the LinkValidator task:

.. figure:: ../Images/SchedulerTask.png
   :alt: The LinkValidator Scheduler task

   Defining the LinkValidator task in the Scheduler


-   With this task you can run LinkValidator regularly via cron without
    having to manually update the stored information on broken links.

-   You can for example overwrite the TSconfig configuration. Without any change,
    the LinkValidator settings which apply for the respective pages will
    be used. If you set values there, the former will be overwritten.

-   The LinkValidator task can send you a status report via email. You can
    create your own email template as needed.

.. _credits:

Credits
^^^^^^^

This extension is particularly based on the extension
"cag\_linkchecker", which was originally developed for Connecta AG,
Wiesbaden. cag\_linkchecker is maintained by Jochen Rieger and Dimitri
König.


.. _feedback:

Feedback
^^^^^^^^

If you find a bug in this manual or in the extension in general,
please file an issue in the
`TYPO3 bug tracker <https://forge.typo3.org/projects/typo3cms-core/issues>`__.

