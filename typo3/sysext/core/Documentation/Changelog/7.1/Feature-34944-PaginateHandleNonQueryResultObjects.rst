
.. include:: ../../Includes.txt

=====================================================================
Feature: #34944 - PaginateViewHelper handles non-query-result objects
=====================================================================

See :issue:`34944`

Description
===========

The PaginateViewHelper accepts input collections of following types:

- :code:`QueryResultInterface`
- :code:`ObjectStorage`
- :code:`\ArrayAccess`
- :code:`array`

.. code-block:: html

	<f:widget.paginate objects="{blogs}" as="paginatedBlogs">
		<f:for each="{paginatedBlogs}" as="blog">
			<h4>{blog.title}</h4>
		</f:for>
	</f:widget.paginate>


.. index:: Fluid