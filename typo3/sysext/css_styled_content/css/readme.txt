***************************
Example stylesheets.
***************************
These example stylesheets are for testing or modelling your own styles for TYPO3 content elements.


----------------------------
TypoScript Setup:
----------------------------
To use these stylesheets in a very simple TypoScript configuration you can use something like this:


	page = PAGE
	page.typeNum = 0
	page.stylesheet = EXT:css_styled_content/css/example.css
	
	page.10 < styles.content.get


The value "EXT:css_styled_content/css/example.css" points to the example stylesheet "example.css". Just change that filename to another of the example stylesheets if you need to.
Notice: Do NOT alter the example stylesheets directly! Rather create a copy of one of them in for example "fileadmin/css/" folder. Then you just change the stylesheet value to "fileadmin/css/example.css" (or what other name you gave the stylesheet!)
	

----------------------------
example.css
----------------------------
Normal stylesheet which defines relatively normal styles for all elements.
Good to use as a basis for your own stylesheets.


----------------------------
example_outlines.css
----------------------------
Test stylesheet that enhances the edges of all elements drastically!



