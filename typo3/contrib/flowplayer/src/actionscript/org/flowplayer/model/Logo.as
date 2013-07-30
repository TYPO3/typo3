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

    import org.flowplayer.model.DisplayPropertiesImpl;
	import org.flowplayer.util.URLUtil;

	/**
	 * @author api
	 */
	public class Logo extends DisplayPluginModelImpl {
		
		private var _fullscreenOnly:Boolean = true;
		private var _fadeSpeed:Number;
		private var _displayTime:int = 0;
		private var _linkUrl:String;
		private var _linkWindow:String;
		
		public function Logo(disp:DisplayObject, name:String):void {
            super(disp, name, false);
            name = "logo";
			top = "20";
			right = "20";
            alpha = 1;

			_linkWindow = "_self";
		}

        override public function clone():Cloneable {
            var copy:Logo = new Logo(getDisplayObject(), name);
            copyFields(this, copy);
            copy.url = url;
            copy.fullscreenOnly = _fullscreenOnly;
            copy.fadeSpeed = _fadeSpeed;
            copy.displayTime = _displayTime;
            copy.linkUrl = _linkUrl;
            copy.linkWindow = _linkWindow;
            return copy;
        }

        [Value]
		public function get fullscreenOnly():Boolean {
			return _fullscreenOnly;
		}
		
		public function set fullscreenOnly(fullscreenOnly:Boolean):void {
			_fullscreenOnly = fullscreenOnly;
		}
        [Value]
		public function get fadeSpeed():Number {
			return _fadeSpeed;
		}
		
		public function set fadeSpeed(fadeSpeed:Number):void {
			_fadeSpeed = fadeSpeed;
		}
		
        [Value]
		public function get displayTime():int {
			return _displayTime;
		}
		
		public function set displayTime(displayTime:int):void {
			_displayTime = displayTime;
		}
		
        [Value]
		public function get linkUrl():String {
			return _linkUrl;
		}
		
		public function set linkUrl(linkUrl:String):void {
			if(URLUtil.isValid(linkUrl))
				_linkUrl = linkUrl;
		}
		
        [Value]
		public function get linkWindow():String {
			return _linkWindow;
		}
		
		public function set linkWindow(linkWindow:String):void {
			_linkWindow = linkWindow;
		}
	}
}
