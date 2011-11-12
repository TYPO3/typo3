/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.util {
	import flash.display.BlendMode;
	import flash.system.Capabilities;
	import flash.text.AntiAliasType;
	import flash.text.Font;
	import flash.text.TextField;
	import flash.text.TextFormat;	
	/**
	 * @author api
	 */
	public class TextUtil {

		private static var log:Log = new Log("org.flowplayer.util::TextUtil");
		public static function createTextField(embedded:Boolean, font:String = null, fontSize:int = 12, bold:Boolean = false):TextField {
			var field:TextField = new TextField();
			field.blendMode = BlendMode.LAYER;
			field.embedFonts = embedded;
			var format:TextFormat = new TextFormat();
			if (font) {
				log.debug("Creating text field with font: " + font);
				format.font = font;
				field.antiAliasType = AntiAliasType.ADVANCED;
			} else {
				if (Capabilities.os.indexOf("Windows") == 0) {
					format.font = getFont(["Lucida Grande", "Lucida Sans Unicode", "Bitstream Vera", "Verdana", "Arial", "_sans", "_serif"]);
					format.font = "_sans";
				} else {		
					format.font = "Lucida Grande, Lucida Sans Unicode, Bitstream Vera, Verdana, Arial, _sans, _serif";
					field.antiAliasType = AntiAliasType.ADVANCED;
				}
			}
			format.size = fontSize;
			format.bold = bold;
			format.color = 0xffffff;
			field.defaultTextFormat = format;
			return field;
		}
		
		private static function getFont(fontList:Array):String {
			var available:Array = Font.enumerateFonts(true);
			for (var i:Number = 0; i < fontList.length; i++) {
				for (var j:Number = 0; j < available.length; j++) {
					if (fontList[i] == Font(available[j]).fontName) {
						return fontList[i];
					}
				}
			}
			return null;
		}
	}
}
