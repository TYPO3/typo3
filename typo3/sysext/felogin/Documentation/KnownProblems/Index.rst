.. include:: /Includes.rst.txt

.. _known-problems:

==============
Known Problems
==============

- If there is more than one felogin plugin on a page the password
  recovery option can cause problems. This is a general problem with
  plugins, but in this case the cause is a small hash in the forgot
  password form which is stored in the frontend user session data.
  With multiple instances on a page only one of the hashes is
  stored and only one of the forgot password forms will work. Make sure
  there is only one felogin plugin on the page where the password
  recovery form is displayed.
