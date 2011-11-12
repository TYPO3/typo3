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
    import org.flowplayer.flow_internal;

    use namespace flow_internal;
	/**
	 * @author anssi
	 */
	public class Playlist extends ClipEventSupport {

		private var _currentPos:Number;
        private var _inStreamClip:Clip;
		private var _commonClip:Clip;
		private var _clips:Array;

		public function Playlist(commonClip:Clip = null) {
			if (commonClip == null) {
				commonClip = new NullClip();
			}
			super(commonClip);
			_commonClip = commonClip;
			_commonClip.setParentPlaylist(this);
			initialize();		
		}
		
		private function initialize(newClips:Array = null):void {			
			_clips = new Array();
            _inStreamClip = null;
			if (newClips) {
				for (var i:Number = 0; i < newClips.length; i++) {
					doAddClip(newClips[i]);
				}
			}
			super.setClips(_clips);
			_currentPos = 0;
            log.debug("initialized, current clip is " + current);
		}

		// doc: PlayEventType.PLAYLIST_CHANGED

		/**
		 * Discards all clips and adds the specified clip to the list.
		 */
		public function replaceClips(clip:Clip):void {
			doReplace([clip]);
		}

		/**
		 * Discards all clips and addes the specified clips to the list.
		 */
		public function replaceClips2(clips:Array):void {
			doReplace(clips);
		}

		override flow_internal function setClips(clips:Array):void {
			for (var i:Number = 0; i < clips.length; i++) {
				doAddClip(clips[i], -1, false);
			}
			super.setClips(_clips);
		}
		
		private function doReplace(newClips:Array, silent:Boolean = false):void {
            var oldClips:Array = _clips.concat([]);
			initialize(newClips);
            if (! silent) {
                dispatchPlaylistReplace(oldClips);
            }
		}

        flow_internal function dispatchPlaylistReplace(oldClips:Array = null):void {
            log.debug("dispatchPlaylistReplace");
            var oldClipsEventHelper:ClipEventSupport = new ClipEventSupport(_commonClip, oldClips || []);        
            doDispatchEvent(new ClipEvent(ClipEventType.PLAYLIST_REPLACE, oldClipsEventHelper), true);        }


        /**
         * Adds a new clip into the playlist. Insertion of clips does not change the current clip.
         * @param clip
         * @param pos optional insertion point, if not given the clip is added to the end of the list.
         * @param silent if true does not dispatch the CLIP_ADD event
         * @see ClipEventType#CLIP_ADD
         */
        public function addClip(clip:Clip, pos:int = -1, silent:Boolean = false):void {
            var index:Number = positionOf(pos);
            if (clip.position >= 0 || clip.position == -1 || clip.position == -2) {
                addChildClip(clip, pos);
                return;
            }
            log.debug("current clip " + current);
            if (current.isNullClip || current == commonClip) {
                log.debug("replacing common/null clip");
                // we only have the common clip or a common clip, perform a playlist replace!
                doReplace([clip], true);
            } else {
                doAddClip(clip, pos);
                if (pos >= 0 && pos <= currentIndex && hasNext()) {
                    log.debug("addClip(), moving to next clip");
                    next();
                }
                super.setClips(_clips);
            }
            if (! silent) {
                doDispatchEvent(new ClipEvent(ClipEventType.CLIP_ADD, pos >= 0 ? pos : clips.length - 1), true);
            }
        }

        /**
         * Removes the specified child clip.
         * @param clip
         * @return
         */
        public function removeChildClip(clip:Clip):void {
            clip.unbindEventListeners();
            clip.parent.removeChild(clip);
        }

        private function addChildClip(clip:Clip, pos:int, dispatchEvent:Boolean = true):void {
            log.debug("addChildClip " + clip + ", index " + pos + ", dispatchEvenbt " + dispatchEvent);
            if (pos == -1) {
                pos = clips.length - 1;
            }
            var parent:Clip = clips[pos];
            parent.addChild(clip);
            if (clip.position == 0) {
                _clips.splice(_clips.indexOf(parent), 0, clip);
            } else if (clip.position == -1) {
                _clips.splice(_clips.indexOf(parent) + 1, 0, clip);
            }
            clip.setParentPlaylist(this);
            clip.setEventListeners(this);
            if (dispatchEvent) {
                doDispatchEvent(new ClipEvent(ClipEventType.CLIP_ADD, pos, clip), true);
            }
        }

		private function doAddClip(clip:Clip, pos:int = -1, dispatchEvents:Boolean = true):void {
            log.debug("doAddClip() " + clip);
            clip.setParentPlaylist(this);
            var currentInPos:Clip;
            if (pos == -1) {
                _clips.push(clip);
            } else {
                currentInPos = clips[pos];
                _clips.splice(_clips.indexOf(currentInPos.preroll || currentInPos), 0, clip);
            }
            var nested:Array = clip.playlist;
            for (var i:int = 0; i < nested.length; i++) {
                var nestedClip:Clip = nested[i] as Clip;
                addChildClip(nestedClip, pos, dispatchEvents);
            }

            log.debug("clips now " + _clips);

			if (clip != _commonClip) {
                clip.onAll(_commonClip.onClipEvent);
                log.debug("adding listener to all before events, common clip listens to other clips");
                clip.onBeforeAll(_commonClip.onBeforeClipEvent);
            }
        }
		
		/**
		 * Gets the clip with the specified index.
		 * @param index of the clip to retrieve, if -1 returns the common clip
		 */
		public function getClip(index:Number):Clip {
			if (index == -1) return _commonClip;
			if (clips.length == 0) return new NullClip();
			return clips[index];
		}

        public function get length():Number {
            return clips.length;
        }
				
		public function hasNext(skipPreAndPostRolls:Boolean = true):Boolean {
            if (skipPreAndPostRolls) {
                return current.index < length - 1;
            }
            return _currentPos < _clips.length - 1;
		}
		
		public function hasPrevious(skipPreAndPostRolls:Boolean = true):Boolean {
			return (skipPreAndPostRolls ? current.index : _currentPos) > 0;
		}

		public function get current():Clip {
            if (_inStreamClip) return _inStreamClip;
            if (_currentPos == -1) return null;
			if (_clips.length == 0) return new NullClip();
			return _clips[_currentPos];
		}

        public function get currentPreroll():Clip {
            if (_currentPos == -1 ) return null;
            if (_clips.length == 0) return null;
            if (_inStreamClip) return null;
            var parent:Clip = _clips[_currentPos];
            return parent.preroll;
        }

        public function setInStreamClip(clip:Clip):void {
            log.debug("setInstremClip to " + clip);
            if (clip && _inStreamClip) throw new Error("Already playing an instream clip");
            _inStreamClip = clip;
        }
	
		public function set current(clip:Clip):void {
			toIndex(indexOf(clip));
		}
	
		public function get currentIndex():Number {
			return current.index;
		}
		
		public function next(skipPreAndPostRolls:Boolean = true):Clip {
            if (skipPreAndPostRolls) {
                log.debug("skipping pre and post rolls");
                var pos:int = current.index;
                if (pos+1 > length) return null;
                var clip:Clip = clips[pos+1];
                _currentPos = _clips.indexOf(clip.preroll || clip);
                return clip.preroll || clip;
            }
            if (_currentPos+1 >= _clips.length) return null;
            return _clips[++_currentPos];
		}

		public function get nextClip():Clip {
            log.debug("nextClip()");
			if (_currentPos == _clips.length - 1) return null;
			return _clips[_currentPos + 1];
		}
		
		public function get previousClip():Clip {
			if (_currentPos == 0) return null;
            return _clips[_currentPos - 1];
		}
		
		public function previous(skipPreAndPostRolls:Boolean = true):Clip {
            if (skipPreAndPostRolls) {
                log.debug("skipping pre and post rolls");
                var pos:int = current.index;
                if (pos+1 < 0) return null;
                var clip:Clip = clips[pos-1];
                _currentPos = _clips.indexOf(clip.preroll || clip);
                return clip.preroll || clip;
            }
            if (_currentPos - 1 < 0) return null;
            return _clips[--_currentPos];
		}

		public function toIndex(index:Number):Clip {
			if (index < 0) return null;
            var parentClips:Array = clips;
			if (index >= parentClips.length) return null;
			var clip:Clip = parentClips[index];
            _inStreamClip = null;
            _currentPos = _clips.indexOf(clip.preroll || clip);
            return clip.preroll || clip;
		}

        private function positionOf(index:Number):Number {
            var parentClips:Array = clips;
			var clip:Clip = parentClips[index];
            return clip ? _clips.indexOf(clip.preroll || clip) : 0;            
        }
		
		public function indexOf(clip:Clip):Number {
            return clips.indexOf(clip);
		}

		public function toString():String {
			return "[playList] length " + _clips.length + ", clips " + _clips; 
		}
		
		public function get commonClip():Clip {
			return _commonClip;
		}
		
		/**
		 * Does this playlist have a clip with the specified type?
		 */
		public function hasType(type:ClipType):Boolean {
            var clips:Array = _clips.concat(childClips);
            for (var i:Number = 0; i < clips.length; i++) {
                var clip:Clip = Clip(clips[i]);

                if (clip.type == type) {
                    return true;
                }
            }
            return false;
        }
	}
}
