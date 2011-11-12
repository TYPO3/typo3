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
	import org.flowplayer.model.DisplayProperties;	
	
	import flash.display.DisplayObject;
	import flash.geom.Rectangle;	

	public interface Layout {
		
		function addView(view:DisplayObject, listener:Function, properties:DisplayProperties):void;

		function update(view:DisplayObject, properties:DisplayProperties):Rectangle;
		
		function removeView(view:DisplayObject):void;
		
		function getContainer():DisplayObject;
		
		function getBounds(view:Object):Rectangle;

		function draw(disp:DisplayObject = null):void;
	}
}
