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
    import flash.net.SharedObject;
import flash.utils.Dictionary;
    import org.flowplayer.util.Assert;

    internal class TimedPlaylist {
        
        private var _clips:Array;
        private var _clipsByTime:Dictionary;

        public function TimedPlaylist() {
            _clips = [];
            _clipsByTime = new Dictionary();
        }

        public function addClip(clip:Clip):void {
            Assert.notNull(clip, "addClip(), clip cannot be null");
            if (clip.position < 0 && ! clip.isOneShot) {
                throw new Error("clip's childStart time must be greater than zero!");
            }
            _clips.push(clip);
            _clipsByTime[clip.position] = clip;
        }

        public function indexOf(clip:Clip):int {
            return _clips.indexOf(clip);
        }

        public function getClipAt(time:Number):Clip {
            return _clipsByTime[Math.round(time)];
        }

        public function get length():int {
            return _clips.length;
        }

        public function get clips():Array {
            return _clips.concat();
        }

        public function removeClip(clip:Clip):void {
            _clips.splice(_clips.indexOf(clip), 1);
            delete _clipsByTime[clip.position];
        }
    }
}