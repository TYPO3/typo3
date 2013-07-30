package org.flowplayer.controller {
	import org.flowplayer.model.ClipType;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.ClipEventType;	
	import org.flowplayer.model.Cuepoint;	
	import org.flowplayer.util.Log;	
	
	import flash.events.TimerEvent;	
	import flash.utils.Timer;	
	import flash.events.EventDispatcher;	
	
	import org.flowplayer.model.Clip;import flash.utils.getTimer;	
	
	/**
	 * PlayTimeTracker is responsible of tracking the playhead time. It checks
	 * if the clip's whole duration has been played and notifies listeners when
	 * this happens. It's also responsible of firing cuepoints.
	 * 
	 * @author Anssi
	 */
	internal class PlayTimeTracker extends EventDispatcher {

		private var log:Log = new Log(this);
		private var _clip:Clip;
		private var _startTime:int;
		private var _progressTimer:Timer;
		private var _storedTime:int = 0;
		private var _onLastSecondDispatched:Boolean;
		private var _controller:MediaController;
		private var _endDetectTimer:Timer;
		private var _wasPaused:Boolean = false;
		private var _lastTimeDetected:Number;

		public function PlayTimeTracker(clip:Clip, controller:MediaController) {
			_clip = clip;
			_controller = controller;
		}
		
		public function start():void {
			if (_progressTimer && _progressTimer.running)
				stop();
			_progressTimer = new Timer(30);
			_progressTimer.addEventListener(TimerEvent.TIMER, checkProgress);
			_startTime = getTimer();
			log.debug("started at time " + time);
			_progressTimer.start();
			_onLastSecondDispatched = false;
			
			_endDetectTimer = new Timer(100);
		}

		public function stop():void {
			if (!_progressTimer) return;
			_storedTime = time;
			_progressTimer.stop();
			log.debug("stopped at time " + _storedTime);
		}

		public function set time(value:Number):void {
			log.debug("setting time to " + value);
			_storedTime = value;
			_startTime = getTimer();
		}

		public function get time():Number {
			if (! _progressTimer) return 0;
			
			var timeNow:Number = getTimer();
			var _timePassed:Number = _storedTime + (timeNow - _startTime)/1000;

			if (_clip.type == ClipType.VIDEO || _clip.type == ClipType.API) {
				// this is a sanity check that we have played at least one second
				if (getTimer() - _startTime < 2000) {
					return _timePassed;
				}
				return _controller.time;
			}
			
			if (! _progressTimer.running) return _storedTime;
			return _timePassed;
		}

		private function checkProgress(event:TimerEvent):void {
			if (!_progressTimer) {
                // log.debug("no timer running");
                return;
            }
			checkAndFireCuepoints();
			
			if (_clip.live) return;
			var timePassed:Number = time;
			if (! _clip.duration) {
				// The clip does not have a duration, wait a few seconds before stopping the _timer.
				// Duration may become available once it's loaded from metadata.
				if (timePassed > 5) {
					log.debug("durationless clip, stopping duration tracking");
					_progressTimer.stop();
				}
				return;
			}
			
			checkCompletelyPlayed(_clip);
			
			if (! _onLastSecondDispatched && timePassed >= _clip.duration - 1) {
				_onLastSecondDispatched = true;
				_clip.dispatch(ClipEventType.LAST_SECOND);
			}
		}
		
		private function checkCompletelyPlayed(clip:Clip):void {
            // _clip.endLimit is used by the AdSense plugin for some workarounds
			if (durationReached) {
                // durationFromMetadata is zero for images
                completelyPlayed();

            } else if (clip.duration - time < 2 && !_endDetectTimer.running) {
				startEndTimer(clip);
            }
		}

        public function get durationReached():Boolean {
            if (_clip.durationFromMetadata > _clip.duration) {
                return time >= _clip.duration;
            }
            return _clip.duration - time < _clip.endLimit;
        }

		private function startEndTimer(clip:Clip):void {
		
			bindEndListeners();
            _endDetectTimer.addEventListener(TimerEvent.TIMER,
                    function(event:TimerEvent):void {
                        log.debug("last time detected == " + _lastTimeDetected);
                        if(time == _lastTimeDetected && _endDetectTimer.running || durationReached) {
                            log.debug("clip has reached his end, timer stopped");
                            _endDetectTimer.reset();
                            completelyPlayed();
                        }
                        _lastTimeDetected = time;
                    }
			);
			
			log.debug("starting end detect timer");
			_endDetectTimer.start();
			
		}
		
		private function completelyPlayed():void {
			if(_endDetectTimer.running) {
				unbindEndListeners();
				_endDetectTimer.reset();
				_endDetectTimer = null;
			}
			
			stop();
			log.info(this + " completely played, dispatching complete");
			log.info("clip.durationFromMetadata " + _clip.durationFromMetadata);
			log.info("clip.duration " + _clip.duration);
			dispatchEvent(new TimerEvent(TimerEvent.TIMER_COMPLETE));
		}

		private function checkAndFireCuepoints():void {
			var streamTime:Number = _controller.time;
			var timeRounded:Number = Math.round(streamTime*10) * 100;
//			log.debug("checkAndFireCuepoints, rounded stream time is " + timeRounded);
			
			// also get the points from previous rounds, just to make sure we are not skipping any
			var points:Array = collectCuepoints(_clip, timeRounded);
			
			if (! points || points.length == 0) {
				return;
			}
			for (var i:Number = 0; i < points.length; i++) {
				var cue:Cuepoint = points[i];
				log.info("cuePointReached: " + cue);
				if (! alreadyFired(cue)) {
					log.debug("firing cuepoint with time " + cue.time);
					_clip.dispatch(ClipEventType.CUEPOINT, cue);
					cue.lastFireTime = getTimer();
				} else {
                    log.debug("this cuepoint already fired");
                }
			}
		}
		
		private function collectCuepoints(clip:Clip, timeRounded:Number):Array {
			var result:Array = new Array();
			for (var i:Number = 5; i >= 0; i--) {
				result = result.concat(clip.getCuepoints(timeRounded - i * 100));
			}
			return result;
		}

		private function alreadyFired(cue:Cuepoint):Boolean {
			var lastFireTime:int = cue.lastFireTime;
			if (lastFireTime == -1) return false;
			return getTimer() - cue.lastFireTime < 2000;
		}
		
		private function stopTimer(event:ClipEvent):void {
			log.debug("state is paused, endTimer stopped");
			_clip.unbind(stopTimer);
			_endDetectTimer.reset();
			_clip.onResume(restartEndTimer);
		}
		
		private function killTimer(event:ClipEvent):void {
			log.debug("buffer is empty, clip has reached his end");
			_clip.unbind(killTimer);
			_endDetectTimer.reset();
			completelyPlayed();
		}
		
		private function restartEndTimer(event:ClipEvent):void {
			_clip.unbind(restartEndTimer);
			log.debug("restarting timer");
			startEndTimer(_clip);
		}
		
		private function bindEndListeners():void {
			_clip.onPause(stopTimer);
			_clip.onBufferEmpty(killTimer);
		}
		
		private function unbindEndListeners():void {
			_clip.unbind(stopTimer);
			_clip.unbind(killTimer);
		}
//
//		public function get durationReached():Boolean {
//			return _clip.duration > 0 && time >= _clip.duration;
//		}
		
	}
}
