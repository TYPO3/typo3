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

package org.flowplayer.layout {
	import org.flowplayer.util.Log;	
	import org.flowplayer.util.Arrange;	
	
	import flash.display.DisplayObject;
	
	/**
	 * @author api
	 */
	public class Position {
		private var log:Log = new Log(this);
		private var _top:Length = new Length();
		private var _right:Length = new Length();
		private var _bottom:Length = new Length();
		private var _left:Length = new Length();
		
		public function set topValue(top:Object):void {
			setValue("_top", top);
		}
		
		public function get top():Length {
			return _top;
		}
		
		private function setValue(property:String, value:Object):void {
			if (value is Length) {
				this[property] = value;
				log.debug(property + " set to " + value);
			} else {	
				Length(this[property]).value = value;
			}
			Length(this[getOtherProperty(property)]).clear();
		}

		private function getOtherProperty(property:String):String {
			if (property == "_top") return "_bottom";
			if (property == "_bottom") return "_top";
			if (property == "_left") return "_right";
			if (property == "_right") return "_left";
			throw new Error("Trying to set unknown property " + property);
		}
		
		public function set rightValue(value:Object):void {
			setValue("_right", value);
		}

		public function get right():Length {
			return _right;
		}

		public function set bottomValue(value:Object):void {
			setValue("_bottom", value);
		}
		
		public function get bottom():Length {
			return _bottom;
		}
		
		public function set leftValue(value:Object):void {
			setValue("_left", value);
		}
		
		public function get left():Length {
			return _left;
		}
		
		public function set values(value:Array):void {
			setValue("_top", value[0]);
			setValue("_right", value[1]);
			setValue("_bottom", value[2]);
			setValue("_left", value[3]);
		}
		
		public function get values():Array {
			return [ _top.asObject(), _right.asObject(), _bottom.asObject(), _left.asObject() ];
		}
		
		public function clone():Position {
			var clone:Position = new Position();
			clone._top  = _top.clone() as Length;
			clone._right  = _right.clone() as Length;
			clone._bottom  = _bottom.clone() as Length;
			clone._left  = _left.clone() as Length;
			return clone;
		}
		
		public function toString():String {
			return "[Margins] left: " + _left + ", righ " + _right + ", top " + _top + ", bottom " + _bottom;
		}
		
		public function hasValue(property:String):Boolean {
			if (property == "top") return _top.hasValue();
			if (property == "right") return _right.hasValue();
			if (property == "bottom") return _bottom.hasValue();
			if (property == "left") return _left.hasValue();
			return false;
		}

		public function toLeft(containerWidth:Number, width:Number):void {
			if (_left.hasValue()) return;
			if (_right.pct >= 0) {
				_left.pct = 100 - _right.pct;
			}
			if (_right.px > 0) {
				_left.px = containerWidth - width - _right.px;				
			}
			_right.clear();
		}

		public function toRight(containerWidth:Number, width:Number):void {
			if (_right.hasValue()) return;
			if (_left.pct >= 0) {
				_right.pct = 100 - _left.pct;
			}
			if (_left.px > 0) {
				_right.px = containerWidth - width - _left.px;				
			}
			_left.clear();
		}

		public function toTop(containerHeight:Number, height:Number):void {
			if (_top.hasValue()) return;
			if (_bottom.pct >= 0) {
				_top.pct = 100 - _bottom.pct;
			}
			if (_bottom.px > 0) {
				_top.px = containerHeight - height - _bottom.px;
			}
			_bottom.clear();
		}

		public function toBottom(containerHeight:Number, height:Number):void {
			if (_bottom.hasValue()) return;
			if (_top.pct >= 0) {
				_bottom.pct = 100 - _top.pct;
			}
			if (_top.px > 0) {
				_bottom.px = containerHeight - height - _top.px;
			}
			_top.clear();
		}
	}
}