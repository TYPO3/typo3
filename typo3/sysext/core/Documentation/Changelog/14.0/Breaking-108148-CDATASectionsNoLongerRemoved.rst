..  include:: /Includes.rst.txt

..  _breaking-108148-1763289953:

=======================================================================
Breaking: #108148 - CDATA sections in Fluid templates no longer removed
=======================================================================

See :issue:`108148`

Description
===========

Previous versions of Fluid and TYPO3 removed code wrapped in `<![CDATA[ ]]>`
from template files altogether. This meant that it was possible to use CDATA
to comment out template code. This is no longer possible, since CDATA
sections are now interpreted by Fluid in a different way; see
`Feature: #108148 - Alternative Fluid Syntax for CDATA Sections <https://docs.typo3.org/permalink/changelog:feature-108148-1763288461>`_.

Impact
======

`<![CDATA[ ]]>` can no longer be used to comment out code in Fluid template
files.

Affected installations
======================

Installations that contain Fluid templates using `<![CDATA[ ]]>` to comment
out code are affected. A deprecation has been written to the deprecation log
since TYPO3 13.4.21 if this construct is encountered in a Fluid template
during rendering.

Migration
=========

To comment out code in Fluid templates, the
`Comment ViewHelper <f:comment> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-comment>`_
should be used. Since TYPO3 v13, potential Fluid syntax errors are ignored by
this ViewHelper, which allows commenting out invalid Fluid syntax safely.

..  index:: Fluid, NotScanned, ext:fluid
