.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _large-sites:

Hint for large sites
--------------------

If you have a website with many hundreds of pages, checking all links
will take some time and might lead to a time out. It will also need
some resources so that it might make sense to do the check at night.
If you want to check many pages, you should not use the "Check Links"
tab in the backend module of linkvalidator. Use the TYPO3 scheduler
instead. The task provided by linkvalidator will cache the broken
links just like the button "Check Links" would do. Afterwards you can
use the backend module as usual to fix the according elements.

If you still want to check trees with many pages just in time, set the
depth to a reasonable level like 2 or 3. Do not use "infinite".


