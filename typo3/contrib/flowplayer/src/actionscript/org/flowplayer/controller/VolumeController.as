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
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.PlayerEvent;
	import org.flowplayer.util.Log;
	import org.flowplayer.view.PlayerEventDispatcher;
	
	import flash.events.TimerEvent;
	import flash.media.SoundChannel;
	import flash.media.SoundTransform;
	import flash.net.NetStream;
	import flash.utils.Timer;		
	
	use namespace flow_internal;

	/**
	 * @author api
	 */
	public class VolumeController {

		private var log:Log = new Log(this);
		private var _soundTransform:SoundTransform;
		private var _netStream:NetStream;
		private var _storedVolume:VolumeStorage;
		private var _storeDelayTimer:Timer;
		private var _muted:Boolean;
		private var _playerEventDispatcher:PlayerEventDispatcher;
		private var _soundChannel:SoundChannel;

		public function VolumeController(playerEventDispatcher:PlayerEventDispatcher) {
			_playerEventDispatcher = playerEventDispatcher;
			_soundTransform = new SoundTransform();
			restoreVolume();
			_storeDelayTimer = new Timer(2000, 1);
			_storeDelayTimer.addEventListener(TimerEvent.TIMER_COMPLETE, onTimerDelayComplete);
		}

		public function set netStream(netStream:NetStream):void {
			_netStream = netStream;
			setTransform(_muted ? new SoundTransform(0) : _soundTransform);
		}
		
		private function setTransform(transform:SoundTransform):void {
			if (_netStream) {
				_netStream.soundTransform = transform;
			}
			if (_soundChannel) {
				_soundChannel.soundTransform = transform;
			}	
		}

		private function doMute(persistMuteSetting:Boolean):void {
			log.debug("muting volume");
			if (dispatchBeforeEvent(PlayerEvent.mute())) {
				_muted = true;
				setTransform(new SoundTransform(0));
				dispatchEvent(PlayerEvent.mute());
				if (persistMuteSetting)
					storeVolume(true);
			}
		}

		private function unMute():Number {
			log.debug("unmuting volume to level " + _soundTransform.volume);
			if (dispatchBeforeEvent(PlayerEvent.unMute())) {
				_muted = false;
				setTransform(_soundTransform);
				dispatchEvent(PlayerEvent.unMute());
				storeVolume(false);
				}
			return volume;
		}

		public function set volume(volumePercentage:Number):void {
			if (this.volume == volumePercentage) return;
			if (dispatchBeforeEvent(PlayerEvent.volume(volumePercentage))) {
				if (volumePercentage > 100) {
					volumePercentage = 100;
				}
				if (volumePercentage < 0) {
					volume = 0;
				}
				_soundTransform.volume = volumePercentage / 100;
				if (!_muted) {
					setTransform(_soundTransform);
				}
				dispatchEvent(PlayerEvent.volume(this.volume));
				if (!_storeDelayTimer.running) {
					log.info("starting delay timer");
					_storeDelayTimer.start();
				}
			}
		}

		/**
		 * Gets the volume percentage.
		 */
		public function get volume():Number {
			return _soundTransform.volume * 100;
		}

		private function onTimerDelayComplete(event:TimerEvent):void {
			storeVolume();
		}
		
		private function storeVolume(muted:Boolean = false):void {
			log.info("persisting volume level");
			_storeDelayTimer.stop();
			_storedVolume.volume = _soundTransform.volume;
			_storedVolume.muted = muted;
			_storedVolume.persist();
		}
		
		private function restoreVolume():void {
			_storedVolume = LocalSOVolumeStorage.create();
				
			_soundTransform.volume = _storedVolume.volume;
			if (_storedVolume.muted)
				doMute(false);
		}
		
		private function dispatchBeforeEvent(event:PlayerEvent):Boolean {
			return _playerEventDispatcher.dispatchBeforeEvent(event);
		}
		
		private function dispatchEvent(event:PlayerEvent):void {
			_playerEventDispatcher.dispatchEvent(event);
		}
		
		public function get muted():Boolean {
			return _muted;
		}
		
		public function set muted(muted:Boolean):void {
			if (muted) {
				doMute(true);
			} else {
				unMute();
			}
		}
		
		public function set soundChannel(channel:SoundChannel):void {
			_soundChannel = channel;
			setTransform(_muted ? new SoundTransform(0) : _soundTransform);
		}
	}
}
