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
	
	import com.adobe.utils.StringUtil;
	/**
	 * @author api
	 */
	public class StyleSheetUtil {
			
		public static function colorValue(color:Object, defVal:Number = 0xffffff):Number {
			if (! color) return defVal;
			if (color is Number) return color as Number;
			if (color is String) {
				var colorStr:String = StringUtil.trim(color as String);
				if (colorStr.indexOf("#") == 0) {
					return parseInt("0x" + colorStr.substr(1));
				}
				if (colorStr.indexOf("0x") == 0) {
					return parseInt(colorStr);
				}
				if (colorStr == "transparent") {
					return -1;
				}
				if (color.indexOf("rgb") == 0) {
					var input:String = stripSpaces(color as String);
		            var start:int = input.indexOf("(") + 1;
		            input = input.substr(start, input.indexOf(")") - start);
		            var rgb:Array = input.split(",");	
	                return rgb[0] << 16 ^ rgb[1] << 8 ^ rgb[2];
	            }
			}

            return defVal;
        }

		public static function rgbValue(color:Number):Array {
			return [ (color >> 16) & 0xFF, (color >> 8) & 0xFF, color & 0xFF ];
		}

        public static function colorAlpha(color:Object, defVal:Number = 1):Number {
            if (! color) return defVal;
            if (color is String && color.indexOf("rgb") == 0) {
                var rgb:Array = parseRGBAValues(color as String);
                if (rgb.length == 4) {
                    return rgb[3];
                }
            }
			else if (color is String && color == "transparent") {
				return 0;
			}
            return defVal;
        }

        public static function parseRGBAValues(color:String):Array {
            var input:String = stripSpaces(color);
            var start:int = input.indexOf("(") + 1;
            input = input.substr(start, input.indexOf(")") - start);
            return input.split(",");
        }

        public static function stripSpaces(input:String):String {
            var result:String = "";
            for (var j:int = 0; j < input.length; j++) {
                if (input.charAt(j) != " ") {
                    result += input.charAt(j);
                }
            }
            return result;
        }
		
		public static function borderWidth(prefix:String, style:Object, defVal:Number = 1):Number
		{
			if (! hasProperty(prefix, style) ) return defVal;
			if ( hasProperty(prefix+'Width', style) ) {
                return NumberUtil.decodePixels(style[prefix+'Width']);
            }
			return NumberUtil.decodePixels(parseShorthand(prefix, style)[0]);
		}
		
		/**
		 * Border color value of the root style.
		 */
		public static function borderColor(prefix:String, style:Object, defVal:Number = 0xffffff):uint {
			if (hasProperty(prefix + "Color", style)) 
				return colorValue(style[prefix + "Color"]);
			
            if (hasProperty(prefix, style)) {
                return StyleSheetUtil.colorValue(parseShorthand(prefix, style)[2]);
            }
            
			return defVal;
		}

        /**
         * Border alpha of the root style.
         * @return
         */
        public static function borderAlpha(prefix:String, style:Object, defVal:Number = 1):Number {
			if (hasProperty(prefix + "Color", style)) 
				return colorAlpha(style[prefix + "Color"]);
	
            if (hasProperty(prefix, style)) {
                return StyleSheetUtil.colorAlpha(parseShorthand(prefix, style)[2]);
            }
		
            return defVal;
        }
		
		public static function parseShorthand(property:String, style:Object):Array {
			var str:String = style[property];
			
			// if we are between (), remove spaces
			if ( str.indexOf('(') != -1 )
			{
				var firstPart:String = str.substr(0, str.indexOf('(')+1);
				var secondPart:String = str.substr(str.indexOf('(')+1, str.indexOf(')')-str.indexOf('(')-1);
				var thirdPart:String  = str.substr(str.indexOf(')'))
				secondPart = secondPart.split(' ').join('');
				str = firstPart + secondPart + thirdPart;
			}

			return str.split(" ");
		}
		
		public static function hasProperty(prop:String, style:Object):Boolean {
			return style && style[prop] != undefined;
		}
		
	}
}
