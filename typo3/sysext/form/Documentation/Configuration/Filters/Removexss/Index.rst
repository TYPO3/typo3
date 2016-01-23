.. include:: ../../../Includes.txt


.. _reference-filters-removexss:

=========
removexss
=========

This filter will process all incoming data by default. There is no need to
add this filter manually.

It filters the incoming data on possible Cross Site Scripting attacks and
renders the incoming data safely by removing potential XSS code and adding a
replacement string which destroys the tags.

