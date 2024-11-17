.. include:: /Includes.rst.txt

..  _known-problems:

==============
Known problems
==============

..  _usagePitfallsConstants:

Problem with constants in LinkHandler TSConfig
==============================================

It is important, that the `storagePid` is hard coded in the LinkHandler Page
TSConfig, because using constants, e.g. from the site configuration, won't work
here. :ref:`More details <t3coreapi:linkhandler-pagetsconfig>`

..  _known-problems-other:

Other known problems
====================

For more known problems, please refer to the
`open issues for "Redirect Handling"
<https://forge.typo3.org/projects/typo3cms-core/issues?utf8=✓&set_filter=1&f[]=category_id&op[category_id]==&v[category_id][]=1687&f[]=status_id&op[status_id]=o&f[]=&c[]=tracker&c[]=status&c[]=priority&c[]=subject&c[]=assigned_to&c[]=category&c[]=fixed_version&c[]=cf_7&group_by=&t[]=>`__
