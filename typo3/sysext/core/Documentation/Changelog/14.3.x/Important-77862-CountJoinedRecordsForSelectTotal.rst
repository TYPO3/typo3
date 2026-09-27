..  include:: /Includes.rst.txt

..  _important-77862-1790500544:

=========================================================
Important: #77862 - Count joined records for select.total
=========================================================

See :issue:`77862`

Description
===========

The :typoscript:`select.max` and :typoscript:`select.begin` properties support
the special keyword :typoscript:`total`. When a select query uses a join, the
count used to resolve :typoscript:`total` now includes that join. Conditions
on joined tables therefore work with :typoscript:`total` instead of causing
the content query to fail.

..  index:: Frontend, TypoScript, NotScanned, ext:frontend
