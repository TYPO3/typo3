.. include:: ../../Includes.txt

===============================================================================
Feature: #75676 - Load new content element wizard via modal instead of new page
===============================================================================

See :issue:`75676`

Description
===========

Instead of having the new content element wizard in a separate module page it
will be opened in a modal now.

Depending on the position of the "new" button, there are still two different
variants of the wizard. When triggered from within the page module it will open
as a single step wizard that just inserts the selected content element at the
trigger position.

When triggered from within the list module's "new record" action, it will open
as a two step wizard, that offers a selection of possible content elements first
and then shows a position map as the secons step. After selecting the position
the new content element will be inserted there.

The original hooks of the new content element wizards have been kept as is.

.. index:: Backend
