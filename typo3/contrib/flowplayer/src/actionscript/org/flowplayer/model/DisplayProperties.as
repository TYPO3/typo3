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
	public interface DisplayProperties extends Identifiable, Cloneable {
		
		/**
		 * Gets the associated DisplayObject. This is not implemented
		 * as an accessor since we don't want the display object to
		 * be serialized through ExternalInterface.
		 */
		function getDisplayObject():DisplayObject;
		
		function setDisplayObject(displayObject:DisplayObject):void;

		function set width(value:Object):void;
		
		function get widthPx():Number;
		
		function get widthPct():Number;
		
		function set height(value:Object):void;
		
		function get heightPx():Number;
		
		function get heightPct():Number;
		
		function get dimensions():Dimensions;
		
		function set alpha(value:Number):void;
		
		function get alpha():Number;
		
		function set opacity(value:Number):void;
		
		function get opacity():Number;
		
		function set zIndex(value:Number):void;
		
		function get zIndex():Number;
		
		function get display():String;
		
		function set display(value:String):void;
		
		function get visible():Boolean;

		function set top(top:Object):void;
		
		function set right(value:Object):void;
		
		function set bottom(value:Object):void;
		
		function set left(value:Object):void;
		
		function get position():Position;
	}
}
