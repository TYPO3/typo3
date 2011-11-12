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
	import flash.display.Sprite;
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.LayoutEvent;
	import org.flowplayer.util.Log;

	/**
	 * @author api
	 */
	public class AbstractSprite extends Sprite {

		protected var log:Log = new Log(this);
		
		/**
		 * The managed width value.
		 */
		protected var _width:Number = 0;
		
		/**
		 * The managed height value.
		 */
		protected var _height:Number = 0;
		
		public function setSize(width:Number, height:Number):void {
			_width = width;
			_height = height;
			onResize();
		}
		
		public override function get width():Number {
            if (scaleX != 1) return _width * scaleX;
			return _width || super.width;
		}
		
		public override function set width(value:Number):void {
			setSize(value, height);
		}

		public override function get height():Number {
            if (scaleY != 1) return _height * scaleY;
			return _height || super.height;
		}
		
		public override function set height(value:Number):void {
			setSize(width, value);
		}

        // TODO: make it possible to resize using scaleX and scaleY
//        public override function set scaleX(value:Number):void {
//        }
//
//        public override function set scaleY(value:Number):void {
//        }

		protected function get managedWidth():Number {
			return _width;
		}
		
		protected function get managedHeight():Number {
			return _height;
		}
		
		protected function onResize():void {
		}

		public function draw(event:LayoutEvent):void {
			var bounds:Rectangle = event.layout.getBounds(this);
			setSize(bounds.width, bounds.height);
		}
	}
}
