..  include:: /Includes.rst.txt

..  _breaking-108148-1763289953:

=======================================================================
Breaking: #108148 - CDATA Sections In Fluid Templates No Longer Removed
=======================================================================

See :issue:`108148`

Description
===========

Previous versions of Fluid and TYPO3 removed code wrapped in `<![CDATA[ ]]>`
from template files altogether. This meant that it was possible to use CDATA
to comment-out Template code. This is no longer possible, since CDATA sections
are now interpreted by Fluid in a different way, see
`Feature: #108148 - Alternative Fluid Syntax for CDATA Sections <https://docs.typo3.org/permalink/changelog:feature-108148-1763288461>`_.


Impact
======

`<![CDATA[ ]]>` can no longer be used to comment-out code in Fluid template
files.


Affected installations
======================

Installations that contain Fluid templates that use `<![CDATA[ ]]>` to comment-out
code in Fluid templates. A deprecation is written to the deprecation log since
TYPO3 13.4.21 if this is encountered in a Fluid template during rendering.


Migration
=========

To comment-out code in Fluid templates, the
`Comment ViewHelper <f:comment> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-comment>`_
should be used. Since TYPO3 13, potential Fluid syntax errors are ignored by these
ViewHelpers, which allows commenting-out of invalid Fluid syntax.

..  index:: Fluid, NotScanned, ext:fluid
