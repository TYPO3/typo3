/*    
 *    Copyright 2008 Anssi Piirainen
 *
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

package org.flowplayer.layout {
	import org.flowplayer.util.Log;	
	import org.flowplayer.util.Arrange;	
	
	import flash.display.DisplayObject;
	
	import org.flowplayer.model.DisplayProperties;
	
	import com.adobe.utils.StringUtil;	

	/**
	 * @author api
	 */
	public class LengthMath {
		private static const log:Log = new Log("org.flowplayer.layout::LengthMath");
		
		public static function sum(props:DisplayProperties, valuesToAdd:Object, container:DisplayObject):DisplayProperties {
			var containerWidth:Number = Arrange.getWidth(container);
			var containerHeight:Number = Arrange.getHeight(container);
			
			addValue(props, valuesToAdd, "alpha");
			addValue(props, valuesToAdd, "opacity");
			addValue(props, valuesToAdd, "display");
			addValue(props, valuesToAdd, "visible");
			addValue(props, valuesToAdd, "zIndex");
			
			addDimension("width", props, valuesToAdd, dimToPx(containerWidth), dimToPct(containerWidth));
			addDimension("height", props, valuesToAdd, dimToPx(containerHeight), dimToPct(containerHeight));
			log.debug("sum(): result dimensions " + props.dimensions);

			log.debug("sum(), current position " + props.position);
			var height:Number = props.dimensions.height.toPx(containerHeight);
			if (hasValue(valuesToAdd, "top")) {
				props.position.toTop(containerHeight, height);
				addPosition("top", props, valuesToAdd, height, posToPx(height, containerHeight), posToPct(height, containerHeight));

			} else if (hasValue(valuesToAdd, "bottom")) {
				props.position.toBottom(containerHeight, height);
				addPosition("bottom", props, valuesToAdd, height, posToPx(height, containerHeight), posToPct(height, containerHeight));
			}
			
			var width:Number = props.dimensions.width.toPx(containerWidth);
			if (hasValue(valuesToAdd, "left")) {
				log.debug("adding to left");
				props.position.toLeft(containerWidth, width);
				addPosition("left", props, valuesToAdd, width, posToPx(width, containerWidth), posToPct(width, containerWidth));

			} if (hasValue(valuesToAdd, "right")) {
				props.position.toRight(containerWidth, width);
				addPosition("right", props, valuesToAdd, width, posToPx(width, containerWidth), posToPct(width, containerWidth));
			}
			log.debug("sum(): result position " + props.position);
			return props;
		}

		private static function addValue(props:DisplayProperties, valuesToAdd:Object, prop:String):void {
            if (! valuesToAdd) return;
            if (! props) return;
			if (! containsValue(valuesToAdd[prop])) return;
			props[prop] = valuesToAdd[prop];
		}

		private static function addDimension(dimProp:String, to:DisplayProperties, valuesToAdd:Object, widthToPxFunc:Function, widthToPctFunc:Function):void {
			var width:Object = valuesToAdd[dimProp];
			if (! containsValue(width)) return;
			if (incremental(width)) {
				to[dimProp] = to.dimensions[dimProp].plus(new Length(width), widthToPxFunc, widthToPctFunc);
				log.debug("new dimension is " + to.dimensions[dimProp]);
			} else {
				to[dimProp] = width;
			}
		}
		
		private static function addPosition(posProp:String, to:DisplayProperties, valuesToAdd:Object, height:Number, toPxFunc:Function, toPctFunc:Function):void {
			var top:Object = valuesToAdd[posProp];
			if (incremental(top)) {
				log.debug("adding incremental position value " + top);
				var pos:Length = to.position[posProp].plus(new Length(top), toPxFunc, toPctFunc);
				if (pos.px < 0) {
					pos.px = 0;
				}
				to[posProp] = pos;
			} else {
				to[posProp] = top;
			}
		}
		
		private static function posToPct(dim:Number, containerDim:Number):Function {
			return function(px:Number):Number {
				return ((px + dim/2) / containerDim) * 100;
			};
		}

		private static function posToPx(dim:Number, containerDim:Number):Function {
			return function(pct:Number):Number {
				return pct/100 * containerDim - dim/2;
			};
		}

		private static function dimToPct(containerDim:Number):Function {
			return function(px:Number):Number {
				return px / containerDim * 100;
			};
		}

		private static function dimToPx(containerDim:Number):Function {
			return function(pct:Number):Number {
				return containerDim * pct / 100;
			};
		}

		private static function incremental(width:Object):Boolean {
			if (! width is String) return false;
			var result:Boolean = StringUtil.beginsWith(String(width), "+") || StringUtil.beginsWith(String(width), "-");
			log.debug("incremental? " + width + ", " + result);
			return result;
		}
		
		private static function hasValue(valueObj:Object, prop:String):Boolean {
			return containsValue(valueObj[prop]);
		}

		private static function containsValue(val:Object):Boolean {
			if (val is String) return true;
			if (val is Boolean) return true;
			var result:Boolean = val is Number && ! isNaN(val as Number);
			log.debug("hasValue? " + val + ", " + result);
			return result;
		}
	}
}
