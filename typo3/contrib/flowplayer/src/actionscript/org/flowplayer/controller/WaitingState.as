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
    import org.flowplayer.model.Clip;
import org.flowplayer.model.ClipEventType;
	
	import flash.utils.Dictionary;
	
	import org.flowplayer.controller.PlayListController;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.State;	

	/**
	 * @author api
	 */
	internal class WaitingState extends PlayState {

		public function WaitingState(stateCode:State, playList:Playlist, playListController:PlayListController, providers:Dictionary) {
			super(stateCode, playList, playListController, providers);
		}
		
		internal override function play():void {
			log.debug("play()");
			if (! playListReady) return;
			bufferingState.nextStateAfterBufferFull = playingState;
			if (dispatchBeforeEvent(ClipEventType.BEGIN, [false], false)) {
				playList.current.played = true;
				changeState(bufferingState);
				onEvent(ClipEventType.BEGIN, [false]);
			}
		}

        override internal function stop(closeStreamAndConnection:Boolean = false, silent:Boolean = false):void {
            if (closeStreamAndConnection) {
                stop(true);
            }
        }

		internal override function startBuffering():void {
			if (! playListReady) return;
			log.debug("startBuffering()");
			bufferingState.nextStateAfterBufferFull = pausedState;
			if (dispatchBeforeEvent(ClipEventType.BEGIN, [true], true)) {
				changeState(bufferingState);
				onEvent(ClipEventType.BEGIN, [true]);
			}
		}
	}
}
