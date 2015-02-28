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
    public class Canvas {
        private var _style:Object;
        private var _linkUrl:String;
        private var _linkWindow:String = '_self';

        public function get linkUrl():String {
            return _linkUrl;
        }

        public function set linkUrl(val:String):void {
            _linkUrl = val;
        }

        public function get linkWindow():String {
            return _linkWindow;
        }

        public function set linkWindow(val:String):void {
            _linkWindow = val;
        }

        public function Canvas() {

        }

        public function get style():Object {
            return _style;
        }

        public function set style(val:Object):void {
            _style = val;
        }
    }
}