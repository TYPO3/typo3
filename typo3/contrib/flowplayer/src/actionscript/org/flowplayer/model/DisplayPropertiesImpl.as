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

package org.flowplayer.model {
	import flash.display.DisplayObject;
	
	import org.flowplayer.layout.Dimensions;
	import org.flowplayer.layout.Position;
	import org.flowplayer.model.Cloneable;	

	/**
	 * @author anssi
	 */
	public class DisplayPropertiesImpl extends PluginEventDispatcher implements DisplayProperties {

		private var _name:String;
		private var _display:String = "block";
		private var _dimensions:Dimensions = new Dimensions();
		private var _alpha:Number = 1;
		private var _zIndex:Number = -1;
		private var _position:Position = new Position();
		private var _displayObject:DisplayObject;

		public function DisplayPropertiesImpl(disp:DisplayObject = null, name:String = null, setDefaults:Boolean = true) {
			_displayObject = disp;
			_name = name;
			if (! setDefaults) return;
			alpha = 1;
			display = "block";
			left = "50%";
			top = "50%";
			if (disp) {
				width = disp.width || "50%";
				height = disp.height || "50%";
			}
		}

		public function clone():Cloneable {
			var copy:DisplayPropertiesImpl = new DisplayPropertiesImpl();
			copyFields(this, copy);
			return copy;
		}

		protected function copyFields(from:DisplayProperties, to:DisplayPropertiesImpl):void {
			to._dimensions = from.dimensions.clone() as Dimensions;
			to._alpha = from.alpha;
			to._zIndex = from.zIndex;
			to._name = from.name;
			to._display = from.display;
			to._displayObject = from.getDisplayObject();
			to._position = from.position.clone();
		}

		public static function fullSize(name:String):DisplayPropertiesImpl {
			var props:DisplayPropertiesImpl = new DisplayPropertiesImpl();
			props.name = name;
			props.left = "50%";			
			props.top = "50%";
			props.width = "100%";
			props.height = "100%";
			return props;
		}

		public function getDisplayObject():DisplayObject {
			return _displayObject;
		}
		
		public function setDisplayObject(displayObject:DisplayObject):void {
			_displayObject = displayObject;
		}

		public function set width(value:Object):void {
			_dimensions.widthValue = value;
		}
		
		public function get widthPx():Number {
			return _dimensions.width.px;
		}
		
		public function get widthPct():Number {
			return _dimensions.width.pct;
		}
		
		public function set height(value:Object):void {
			_dimensions.heightValue = value;
		}
		
		public function get heightPx():Number {
			return _dimensions.height.px;
		}
		
		public function get heightPct():Number {
			return _dimensions.height.pct;
		}

		public function set alpha(value:Number):void {
			_alpha = value;
		}
		
		public function get alpha():Number {
			return _alpha;
		}
		
		public function set zIndex(value:Number):void {
			_zIndex = value;
		}
		
		[Value]
		public function get zIndex():Number {
			return _zIndex;
		}
		
		[Value]
		public function get display():String {
			return _display;
		}
		
		public function set display(value:String):void {
			_display = value;
		}
		
		public function get visible():Boolean {
			return _display == "block";
		}

		public function toString():String {
			return "[DisplayPropertiesImpl] '" + _name + "'";
		}
		
		[Value]
		override public function get name():String {
			return _name;
		}
		
		public function set name(name:String):void {
			_name = name;
		}

		public function get position():Position {
			return _position;
		}

		public function set top(top:Object):void {
			_position.topValue = top;
		}
		
		public function set right(value:Object):void {
			_position.rightValue = value;
		}
		
		public function set bottom(value:Object):void {
			_position.bottomValue = value;
		}
		
		public function set left(value:Object):void {
			_position.leftValue = value;
		}
		
		public function hasValue(property:String):Boolean {
			return _position.hasValue(property) || _dimensions.hasValue(property);
		}
		
		public function set opacity(value:Number):void {
			alpha = value;
		}
		
		[Value]
		public function get opacity():Number {
			return alpha;
		}
		
		public function get dimensions():Dimensions {
			return _dimensions;
		}
		
		[Value(name="width")]
		public function get widthObj():Object {
			return _dimensions.width.asObject();
		}
		
		[Value(name="height")]
		public function get heightStr():Object {
			return _dimensions.height.asObject();
		}

		[Value(name="top")]
		public function get topStr():Object {
			return _position.top.asObject();
		}

		[Value(name="right")]
		public function get rightStr():Object {
			return _position.right.asObject();
		}

		[Value(name="bottom")]
		public function get bottomStr():Object {
			return _position.bottom.asObject();
		}

		[Value(name="left")]
		public function get leftStr():Object {
			return _position.left.asObject();
		}
	}
}
