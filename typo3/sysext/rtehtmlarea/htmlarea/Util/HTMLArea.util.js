/***************************************************
 *  UTILITY FUNCTIONS
 ***************************************************/
Ext.apply(HTMLArea.util, {
	/*
	 * Perform HTML encoding of some given string
	 * Borrowed in part from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
	 */
	htmlDecode: function (str) {
		str = str.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
		str = str.replace(/&nbsp;/g, '\xA0'); // Decimal 160, non-breaking-space
		str = str.replace(/&quot;/g, '\x22');
		str = str.replace(/&#39;/g, "'");
		str = str.replace(/&amp;/g, '&');
		return str;
	},
	htmlEncode: function (str) {
		if (typeof(str) != 'string') {
			str = str.toString();
		}
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
		str = str.replace(/\xA0/g, '&nbsp;'); // Decimal 160, non-breaking-space
		str = str.replace(/\x22/g, '&quot;'); // \x22 means '"'
		return str;
	}
});
