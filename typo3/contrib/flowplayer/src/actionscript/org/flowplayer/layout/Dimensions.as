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
	import flash.display.DisplayObject;
	
	import org.flowplayer.model.Cloneable;
	import org.flowplayer.util.Arrange;	

	/**
	 * @author api
	 */
	public class Dimensions implements Cloneable {
		
		private var _width:Length = new Length();
		private var _height:Length = new Length();
		
		public function clone():Cloneable {
			var clone:Dimensions = new Dimensions();
			clone._width = _width.clone() as Length;
			clone._height = _height.clone() as Length;
			return clone;
		}
		
		public function get width():Length {
			return _width;
		}
		
		public function set widthValue(width:Object):void {
			if (width is Length) {
				_width = width as Length;
			} else {
				_width.value = width;
			}
		}
		
		public function get height():Length {
			return _height;
		}
		
		public function set heightValue(height:Object):void {
			if (height is Length) {
				_height = height as Length;
			} else {
				_height.value = height;
			}
		}
		
		public function fillValues(container:DisplayObject):void {
			if (_width.px >= 0)
				_width.pct = _width.px / Arrange.getWidth(container) * 100;
			else if (_width.pct >= 0)
				_width.px = width.pct/100 * Arrange.getWidth(container);
				
			if (_height.px >= 0)
				_height.pct = _height.px / Arrange.getHeight(container) * 100;
			else if (_height.pct >= 0)
				_height.px = height.pct/100 * Arrange.getHeight(container);
		}
		
		public function toString():String {
			return "(" + _width + ") x (" + _height + ")";
		}
		
		public function hasValue(property:String):Boolean {
			if (property == "width") return _width.hasValue();
			if (property == "height") return _height.hasValue();
			return false;
		}
	}
}
