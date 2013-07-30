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

package org.flowplayer.controller {
	import flash.utils.Dictionary;
	
	import org.flowplayer.controller.PlayListController;
    import org.flowplayer.model.ClipEventSupport;
import org.flowplayer.model.ClipEventType;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.State;
	import org.flowplayer.model.Status;	

	/**
	 * @author api
	 */
	internal class PausedState extends PlayState {
		
		public function PausedState(stateCode:State, playList:Playlist, playListController:PlayListController, providers:Dictionary) {
			super(stateCode, playList, playListController, providers);
		}

        override protected function setEventListeners(eventSupport:ClipEventSupport, add:Boolean = true):void {
            if (add) {
                log.debug("adding event listeners");
                eventSupport.onStop(onClipStop);
            } else {
                eventSupport.unbind(onClipStop);
            }
        }

		internal override function play():void {
            resume();
		}
		
		internal override function resume(silent:Boolean = false):void {
			log.debug("resume(), changing to stage " + playingState);
			if (silent || dispatchBeforeEvent(ClipEventType.RESUME, [silent])) {
				changeState(playingState);
				onEvent(ClipEventType.RESUME, [silent]);
			}
		} 

		internal override function stopBuffering():void {
			log.debug("stopBuffering() called");
			stop(true);
		}

		internal override function seekTo(seconds:Number, silent:Boolean = false):void {
			if (silent || dispatchBeforeEvent(ClipEventType.SEEK, [seconds, silent], seconds))
				onEvent(ClipEventType.SEEK, [seconds, silent]);
		}

        //fix for #279, switchStream method missing for paused state
        internal override function switchStream(netStreamPlayOptions:Object = null):void {
            log.debug("switchStream()");
            if (dispatchBeforeEvent(ClipEventType.SWITCH, [netStreamPlayOptions]))
                onEvent(ClipEventType.SWITCH, [netStreamPlayOptions]);
        }
	}
}
