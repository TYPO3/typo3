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

package org.flowplayer.view {
	import org.flowplayer.layout.Length;	
	import org.flowplayer.util.GraphicsUtil;	
	import org.flowplayer.util.NumberUtil;	
	
	import flash.text.StyleSheet;
	
	import org.flowplayer.util.Log;
	import org.flowplayer.util.StyleSheetUtil;
	import org.flowplayer.view.FlowStyleSheet;
	
	import com.adobe.utils.StringUtil;	

	/**
	 * An extension of the Flash's StyleSheet class. It adds a possibility to
	 * specify a border and a background image. The style properties of the
	 * border and backgound image are exposed by the methods of this class.
	 * 
	 * @author api
	 */
	public class FlowStyleSheet {
		private var log:Log = new Log(this);
		internal static const ROOT_STYLE_PROPS:Array =  [
		"padding", "backgroundColor", "backgroundGradient", "border", "borderColor", "borderRadius", "borderWidth",
            "backgroundImage", "backgroundRepeat", "background", "linkUrl", "linkWindow", "textDecoration"];
		private var _styleSheet:flash.text.StyleSheet;
		private var _styleName:String;
		/**
		 * Creates a new stylesheet.
		 * @param rootStyleName the style name that holds the border and backgound image and other extensions
		 * @param CSS stylesheet as a string, this styleshoot should contain the style properties for the specified styleName. It
		 * can also contain additional properties and those can be accessed via the styleSheet property
		 */
		public function FlowStyleSheet(rootStyleName:String, cssText:String = null) {
			_styleName = rootStyleName;
			_styleSheet = new flash.text.StyleSheet();
			if (cssText) {
				parseCSS(cssText);
			}
		}
		
		public static function isRootStyleProperty(prop:String):Boolean {
			return ROOT_STYLE_PROPS.indexOf(prop) >= 0;
		}

		/**
		 * Gets the root style name.
		 */
		public function get rootStyleName():String {
			return _styleName;
		}

		/**
		 * Gets the root style object.
		 */
		public function get rootStyle():Object {
			return _styleSheet.getStyle(_styleName);
		}

		/**
		 * Sets the root style.
		 */
		public function set rootStyle(styleObj:Object):void {
			setStyle(_styleName, styleObj);
		}

		/**
		 * Sets the style with the specified name.
		 */
		public function setStyle(styleName:String, styleObj:Object):void {
			_styleSheet.setStyle(styleName, styleObj);
		}
		
		/**
		 * Sets the style with the specified name.
		 */
		public function getStyle(styleName:String):Object {
			return _styleSheet.getStyle(styleName);
		}
		/**
		 * Adds style proeperties to the root style.
		 */
		public function addToRootStyle(style:Object):void {
			addStyleRules(_styleName, style);
		}
		
		/**
		 * Adds the specified style properties to the specified style.
		 */
		public function addStyleRules(styleName:String, style:Object):void {
			var current:Object = _styleSheet.getStyle(styleName);
			for (var prop:String in style) {
				current[prop] = style[prop];
			}
			_styleSheet.setStyle(styleName, null);
			_styleSheet.setStyle(styleName, current);
		}

		/**
		 * The underlying stylesheet.
		 */
		public function get styleSheet():StyleSheet {
			return _styleSheet;
		}
		

		/**
		 * The padding of the root style.
		 */
		public function get padding():Array {
			if (! StyleSheetUtil.hasProperty("padding", rootStyle)) return [5, 5, 5, 5];
			var paddingStr:String = rootStyle["padding"];
			
			if (paddingStr.indexOf(" ") > 0) {
				var pads:Array = new Array();
				var values:Array = paddingStr.split(" ");
				for (var i:Number = 0; i < values.length; i++) {
					var value:String = values[i];
					pads[i] = NumberUtil.decodePixels(value);
				}
				return pads;
			}
			else {
				var pxVal:int = NumberUtil.decodePixels(paddingStr);
				var result:Array = new Array();
				// we cannot just return [ pxVal, pxVal, pxVal, pxVal ] because that gives a stack overflow error??? why??
				result.push(pxVal);
				result.push(pxVal);
				result.push(pxVal);
				result.push(pxVal);
				return result;
			}
		}
		
		/**
		 * Background color of the root style.
		 */
		public function get backgroundColor():uint {
            if (StyleSheetUtil.hasProperty("background", rootStyle)) {
                return StyleSheetUtil.colorValue(StyleSheetUtil.parseShorthand("background", rootStyle)[0]);
            }
            if (StyleSheetUtil.hasProperty("backgroundColor", rootStyle)) {
                return parseColorValue("backgroundColor");
            }
			return 0x333333;
		}

        /**
         * Background aplpa.
         * @return
         */
        public function get backgroundAlpha():Number {
            if (StyleSheetUtil.hasProperty("background", rootStyle)) {
                return StyleSheetUtil.colorAlpha(StyleSheetUtil.parseShorthand("background", rootStyle)[0]);
            }
            if (StyleSheetUtil.hasProperty("backgroundColor", rootStyle)) {
                return parseColorAlpha("backgroundColor");
            }
            return 1;
        }
		
		/**
		 * Background gradient of the root style.
		 */
		public function get backgroundGradient():Array {
			if (! StyleSheetUtil.hasProperty("backgroundGradient", rootStyle)) return null;
			if (rootStyle["backgroundGradient"] is String) {
				return decodeGradient(rootStyle["backgroundGradient"] as String);
			}
			return rootStyle["backgroundGradient"];
		}
		
		public static function decodeGradient(value:String):Array {
			if (value == "none") return null;
			if (value == "high") return [1, 0.5, 0, 0.1, .3];
			if (value == "medium") return [.6, .21, .21];
			return [.4, .15, .15];
		}

		/**
		 * Is the background transparent in the root style?
		 */
		public function get backgroundTransparent():Boolean {
			if (! StyleSheetUtil.hasProperty("backgroundColor", rootStyle)) return false;
			
			return rootStyle["backgroundColor"] == "transparent" || backgroundAlpha == 0;
		}

		/**
		 * Border weight value of the root style.
		 */
		public function get borderWidth():Number {
            return StyleSheetUtil.borderWidth('border', rootStyle);
		}
		
		/**
		 * Border color value of the root style.
		 */
		public function get borderColor():uint {
            return StyleSheetUtil.borderColor('border', rootStyle);
		}

        /**
         * Border alpha of the root style.
         * @return
         */
        public function get borderAlpha():Number {
            return StyleSheetUtil.borderAlpha('border', rootStyle);
        }
		
		/**
		 * Border radius of the root style.
		 */
		public function get borderRadius():int {
			if (! StyleSheetUtil.hasProperty("borderRadius", rootStyle)) return 5;
			return NumberUtil.decodePixels(rootStyle["borderRadius"]);
		}
		
		/**
		 * Backround image of the rot style.
		 */
		public function get backgroundImage():String {
			if (StyleSheetUtil.hasProperty("backgroundImage", rootStyle)) {
				var image:String = rootStyle["backgroundImage"];
				if (image.indexOf("url(") == 0) {
					return image.substring(4, image.indexOf(")"));
				}
				return rootStyle["backgroundImage"] as String;
			}
			if (StyleSheetUtil.hasProperty("background", rootStyle)) {
				return find(StyleSheetUtil.parseShorthand("background", rootStyle), "url(");
			}
			return null;
		}
		
        /**
         * Gets the link URL associated with this sprite.
         * @return
         */
        public function get linkUrl():String {
            return rootStyle["linkUrl"] as String;
        }

        /**
         * Gets the linkWindow that specifies how the link is opened.
         * @return
         * @see #linkUrl
         */
        public function get linkWindow():String {
            if (! StyleSheetUtil.hasProperty("linkWindow", rootStyle)) return "_self";
            return rootStyle["linkWindow"] as String;
        }

		private function find(background:Array, prefix:String):String {
			for (var i:Number = 0; i < background.length; i++) {
				if (background[i] is String && String(background[i]).indexOf(prefix) == 0) {
					return background[i] as String;
				}
			}
			return null;
		}

		public function get backgroundImageX():Length {
			if (! StyleSheetUtil.hasProperty("background", rootStyle)) return new Length(0);
			var props:Array = StyleSheetUtil.parseShorthand("background", rootStyle);
			if (props.length < 2) return null;
			return new Length(props[props.length - 2]);
		}
		
		public function get backgroundImageY():Length {
			if (! StyleSheetUtil.hasProperty("background", rootStyle)) return new Length(0);
			var props:Array = StyleSheetUtil.parseShorthand("background", rootStyle);
			if (props.length < 1) return null;
			return new Length(props[props.length - 1]);
		}

		/**
		 * Is the background repeated in the root style?
		 */
		public function get backgroundRepeat():Boolean {
			if (StyleSheetUtil.hasProperty("backgroundRepeat", rootStyle)) {
				return rootStyle["backgroundRepeat"] == "repeat";
			}
			if (StyleSheetUtil.hasProperty("background", rootStyle)) {
				return StyleSheetUtil.parseShorthand("background", rootStyle).indexOf("no-repeat") < 0;
			}
			return false;
		}

        public function get textDecoration():String {
            return rootStyle["textDecoration"]; 
        }
		
		private function parseCSS(cssText:String):void {
			_styleSheet.parseCSS(cssText);
			rootStyle = _styleSheet.getStyle(_styleName);
		}
		
		private function parseColorValue(colorProperty:String):uint {
			return StyleSheetUtil.colorValue(rootStyle[colorProperty]);
		}

        private function parseColorAlpha(colorProperty:String):Number {
            return StyleSheetUtil.colorAlpha(rootStyle[colorProperty]);
        }

		

		
	}
}
