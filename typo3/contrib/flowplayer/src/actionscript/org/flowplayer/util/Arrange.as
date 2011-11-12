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
	import flash.display.DisplayObject;
	import flash.display.Stage;
	import flash.display.StageDisplayState;
    import org.flowplayer.model.DisplayProperties;

	/**
	 * @author api
	 */
	public class Arrange {
		public static var parentHeight:Number=0;
		public static var parentWidth:Number=0;
		public static var hasParent:Boolean=false;
		public static var set:Boolean=false;
		public static var localWidth:Number=0;
		public static var localHeight:Number=0;
		/**
		 * Centers the specified display object to the specified area.
		 * @param disp the object to center
		 * @param the width of the centering area
		 * @param the height of the centering area
		 */
		public static function center(disp:DisplayObject, areaWidth:Number = 0, areaHeight:Number = 0):void {
			if (areaWidth > 0)
				disp.x = int((areaWidth / 2) - (disp.width / 2));
			if (areaHeight > 0)
				disp.y = int((areaHeight / 2) - (disp.height / 2));
		}
		
		/**
		 * Resize the specified display object to have the same size as the other specified display object.
		 * @param disp the object to resize
		 * @param other the object where the size is taken from
		 */
		public static function sameSize(disp:DisplayObject, other:DisplayObject):void {
			if (! disp) return;
			if (! other) return;
			if (other is Stage) {
				disp.width =  Stage(other).stageWidth;
				disp.height = Stage(other).stageHeight;
			} else {
				disp.width =  other.width;
				disp.height = other.height;
			}
		}

		/**
		 * Returns a string the describes the specified display object's position and dimensions.
		 * @param disp the object to describe
		 */
		public static function describeBounds(disp:DisplayObject):String {
			return "x: " + disp.x + ", y: " + disp.y + ", width: " + disp.width + ", height: " + disp.height;
		}
		
		/**
		 * Gets the position of the specified display object relative to another object. 
		 * The position is measured from one specified edge of the container object to the center 
		 * of the queried object. The result can be used in CSS style percentage positioning - to
		 * position the specified display object inside the container.
		 * 
		 * @param disp the display object whose position is queried
		 * @param container the display object relative to which the position is calculated
		 * @param edge the edge from which the position is calculated from: 0 means that the 
		 * position is measured from the top, 1 from right, 2 from bottom, and 3 from left
		 */
		public static function positionPercentage(disp:DisplayObject, container:DisplayObject, edge:int):int {
			if (edge == 0 || edge == 2) {
				var topPct:int = ((disp.y + disp.height / 2) / container.height) * 100;
				return edge == 0 ? topPct : 100 - topPct;
			}
			if (edge == 1 || edge == 3) {
				var leftPct:int = ((disp.x + disp.width / 2) / container.width) * 100;
				return edge == 3 ? leftPct : 100 - leftPct;
			}
			return 0;
		}
		
		public static function getWidth(disp:DisplayObject):Number {
			if (disp is Stage) {
				return getStageWidth(disp as Stage);
			} else {
				return disp.width;
			}
		}
		
		public static function getHeight(disp:DisplayObject):Number {
			if (disp is Stage) {
				return getStageHeight(disp as Stage);
			} else {
				return disp.height;
			}
		}

		public static function getStageWidth(stage:Stage):Number {
			return getStageDimension(stage, "width");
		}
		
		public static function getStageHeight(stage:Stage):Number {
			return getStageDimension(stage, "height");
		}
		
		protected static function getStageDimension(stage:Stage, dimensionName:String):Number {
			if (stage.displayState == StageDisplayState.FULL_SCREEN) {
				return dimensionName == "height" ? stage.stageHeight : stage.stageWidth;
			}
			return dimensionName == "height" ? parentHeight : parentWidth;
		}

        public static function fixPositionSettings(props:DisplayProperties, defaults:Object):void {
            clearOpposite("bottom", "top", props, defaults);
            clearOpposite("left", "right", props, defaults);
        }

        private static function clearOpposite(prop1:String, prop2:String, props:DisplayProperties, defaults:Object):void {
            if (props.position[prop1].hasValue() && defaults.hasOwnProperty(prop2)) {
                delete defaults[prop2];
            } else if (props.position[prop2].hasValue() && defaults.hasOwnProperty(prop1)) {
                delete defaults[prop1];
            }
        }
	}
}
