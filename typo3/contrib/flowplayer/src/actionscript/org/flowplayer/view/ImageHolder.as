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
	import flash.display.*;
	import flash.events.*;
	
	import org.flowplayer.util.Log;	

	/**
	 * @author api
	 */
 	public class ImageHolder extends Sprite {

		private var _loader:Loader;
		private var _mask:Sprite;
		
		private var _width:int;
		private var _height:int;
		private var _originalWidth:int;
		private var _originalHeight:int;
		
		private var _widthRatio:Number;
		private var _heightRatio:Number;
		
		public function ImageHolder(loader:Loader) {
			super();
			
			_loader = loader;
			

			_originalWidth  = loader.contentLoaderInfo.width;
			_originalHeight = loader.contentLoaderInfo.height;
			
			_width = loader.width;
			_height= loader.height;
			
			_widthRatio = _width / _originalWidth;
			_heightRatio= _height / _originalHeight;
			
			//width = _originalWidth;
			//height = _originalHeight
			
			graphics.drawRect(0, 0, _originalWidth, _originalHeight);
			addChild(_loader);
			updateMask();
		}
		
		private function updateMask():void
		{
			if ( _mask != null && contains(_mask) )
				removeChild(_mask);
				
			_mask = new Sprite();
			_mask.graphics.beginFill(0xFF0000);
			_mask.graphics.drawRect(0, 0, _width, _height);
			addChild(_mask);
			_loader.mask = _mask;
		}

		public override function get width():Number {
			return _width || super.width;
		}
		
		public override function set width(value:Number):void {
			_width = value;
			_loader.width = value * _widthRatio;
			updateMask();
		}
		
		public override function get height():Number {
			return _height || super.height;
		}
		
		public override function set height(value:Number):void {
			_height = value;
			_loader.height = value * _heightRatio;
			updateMask();
		}
	
		public function get originalWidth():int {
			return _originalWidth;
		}
		
		public function get originalHeight():int {
			return _originalHeight;
		}
		
		public static function hasOffscreenContent(loader:Loader):Boolean
		{
		 	return loader.width != loader.contentLoaderInfo.width || loader.height != loader.contentLoaderInfo.height;
		}
	}
}
