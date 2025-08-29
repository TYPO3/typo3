.. include:: /Includes.rst.txt
.. _known-problems:

==============
Known problems
==============

Searching for hy-phen-at-ed words
=================================
When using the fulltext index feature, searching for words with hyphens in
them ("Berners-Lee") will yield no results when MySQL is used
as database server. MariaDB does not have this problem.

The reason for this behavior is that the MySQL fulltext parser indexes
`words with hyphens as two words`__: "Berners Lee".

Another problem is that the "fulltext search minimum word length" setting
`ft_min_word_len` default value is `4`, which means that three-letter
words are not indexed at all.
Of "Berners-Lee", only "Berners" will be in the index.

__ https://dev.mysql.com/doc/refman/8.4/en/fulltext-fine-tuning.html#fulltext-modify-character-set
