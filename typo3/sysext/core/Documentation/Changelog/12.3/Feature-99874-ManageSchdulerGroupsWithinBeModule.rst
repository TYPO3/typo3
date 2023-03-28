.. include:: /Includes.rst.txt

.. _feature-99874-1678720364:

==============================================================
Feature: #99874 - Edit task groups within the Scheduler module
==============================================================

See :issue:`99874`

Description
===========

Task groups can be managed in the backend module itself. Users can create, update and
delete task groups within the :guilabel:`Scheduler` module. Sorting is done via drag&drop (drag the panel header)
and inline-style editing is used to change the title name. Only empty groups may be deleted.

Impact
======

Users may edit groups in the :guilabel:`Scheduler` module.

..  note::

    The group's description has never been displayed in the :guilabel:`Scheduler` module and has been
    deprecated. Editing the description is and has always been only possible via the :guilabel:`List` module.


.. index:: ext:scheduler
