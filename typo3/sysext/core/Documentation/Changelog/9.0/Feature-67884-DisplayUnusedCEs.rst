.. include:: /Includes.rst.txt

======================================
Feature: #67884 - Display 'unused' CEs
======================================

See :issue:`67884`

Description
===========

Gather all CEs which are not assigned to a valid column of the current backend layout and show them
at the end of the page. This will collect elements that might get lost when switching the backend
layout to another one with different columns.

If at least one element is found that belongs to a missing column, there will be an additional column
"Unused" at the bottom of the current backend layout. This column contains each of the lost elements
without having to change their actual column. So when the layout of the page is changed back, the
elements will nicely fall back into their original column position.

Additionally there will be a warning message that tells the users about the lost elements and how
to possibly deal with them.


Impact
======

In the current state this feature will make all elements visible that have got a colPos value other
than those made available by the backend layout. So elements created by extensions like Gridelements,
Flux/Fluidcontent and others will have their child elements visible in the "Unused" area as well.

This has to be tackled in another patchset, which will introduce NULL values for the colPos field of
tt_content, so these special elements will not be "Unused" but still not visible in the usual
frontend output.

.. index:: Backend
