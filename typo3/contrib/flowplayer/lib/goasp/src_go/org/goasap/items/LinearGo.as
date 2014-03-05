/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap.items {
	import flash.utils.getTimer;
	
	import org.goasap.GoEngine;
	import org.goasap.errors.EasingFormatError;
	import org.goasap.events.GoEvent;
	import org.goasap.interfaces.IPlayable;
	import org.goasap.managers.LinearGoRepeater;	

	/**
	 * Dispatched during an animation's first update after the delay 
	 * has completed, if one was set. Any number of callbacks may also be 
	 * associated with this event using <code>addCallback</code>.
	 * @eventType org.goasap.events.START
	 */
	[Event(name="START", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched on the animation's update pulse. Any number of callbacks
	 * may also be associated with this event using <code>addCallback</code>.
	 * @eventType org.goasap.events.UPDATE
	 */
	[Event(name="UPDATE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when pause() is called successfully.  Any number of callbacks
	 * may also be associated with this event using <code>addCallback</code>.
	 * @eventType org.goasap.events.PAUSE
	 */
	[Event(name="PAUSE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when resume() is called successfully. Any number of callbacks
	 * may also be associated with this event using <code>addCallback</code>.
	 * @eventType org.goasap.events.RESUME
	 */
	[Event(name="RESUME", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched at the end of each cycle if the tween has more than one.
	 * Any number of callbacks may also be associated with this event using 
	 * <code>addCallback</code>.
	 * @eventType org.goasap.events.CYCLE
	 */
	[Event(name="CYCLE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched if an animation is manually stopped. Any number of callbacks
	 * may also be associated with this event using <code>addCallback</code>.
	 * @eventType org.goasap.events.STOP
	 */
	[Event(name="STOP", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched on an animation's final update, just after the last update event.
	 * Any number of callbacks may also be associated with this event using 
	 * <code>addCallback</code>.
	 * @eventType org.goasap.events.COMPLETE
	 */
	[Event(name="COMPLETE", type="org.goasap.events.GoEvent")]

	/**
	 * LinearGo extends the base class GoItem to define a playable A-to-B animation. 
	 * 
	 * <p><b>LinearGo: A very simple tween</b></p>
	 * 
	 * <p>A LinearGo instance is a playable object that animates a single number. It dispatches events 
	 * and callbacks associated with the animation's start, update and completion. Instances can be used 
	 * directly, or easily subclassed to build custom tweening APIs. LinearGo extends GoItem, which 
	 * provides basic settings shared by physics and tween items. These include play-state contants and 
	 * a <code>state</code> property, <code>pulseInterval</code>, and the two common animation options 
	 * <code>useRounding</code> and <code>useRelative</code>.</p>
	 * 
	 * <p>The tween can be customized using the instance properties <code>duration</code>, <code>easing</code> 
	 * and <code>delay</code>. The number crunched by a LinearGo is readable in its <code>position</code> 
	 * property. This number always starts at 0 and completes at 1, regardless of the tween's duration 
	 * or easing (those parameters are factored in to produce accurate fractional in-between values).  
	 * As the tween runs, you can use <code>position</code> as a multiplier to animate virtually anything: 
	 * motion, alpha, a sound level, the values in a ColorTransform, BitmapFilter, a 3D scene, and so on.
	 * Note that at times position may be less than 0 or greater than 1 depending on the easing function.</p>
	 * 
	 * <p>The START event occurs just before the first update (after the delay). UPDATE is fired once on 
	 * <i>every</i> update pulse, and COMPLETE just after the final update. The STOP event is fired by LinearGo 
	 * only if a tween is stopped before it completes. Additional events are fired on PAUSE, RESUME and at
	 * the end of each CYCLE if the tween plays more than one cycle. Besides standard events, you can store 
	 * callback functions (method-closures) using <code>addCallback</code>. Any number of callbacks can be 
	 * associated with each GoEvent type. This alternative to the standard event model was included in 
	 * LinearGo since it's a common feature of many modern tweening APIs, and very slightly more efficient 
	 * than standard events.</p>
	 * 
	 * <p>LinearGo can play multiple back-and-forth tween cycles or repeat forward-play any number of times.
	 * This functionality is handled by the LinearGo's <code>repeater</code> instance, which has settings for
	 * alternate easing on reverse-cycles, infinite cycling, plus <code>currentCycle</code> and <code>done</code> 
	 * state properties.</p>
	 * 
	 * <p><b>Subclassing to create custom tweens</b></p>
	 * 
	 * <p><i>Important: Store your custom tween classes in a package bearing your own classpath, not in the core 
	 * package! This will help avoid confusion with other authors' work.</i></p>
	 * 
	 * <p>It's possible to build virtually any tweening API over LinearGo because all of the specifics are left 
	 * up to you: target objects, tweenable properties, tween values — and importantly, the datatypes of all of these.</p>
	 * 
	 * <p>A basic subclass can be created in three steps: Gathering target & property information, subclassing the 
	 * <code>start</code> method to set up the tween, and finally subclassing the <code>onUpdate</code> method 
	 * to affect the tween. The first step, gathering tween target and property information, can be done by writing 
	 * getter/setter properties, customizing the constructor, or both. Consider various options such as allowing for 
	 * single vs. multiple target objects, open vs. specific tween properties, and so on. The next step, subclassing 
	 * <code>start</code>, involves figuring the tween's amount of change and implementing a standard Go convention, 
	 * <code>useRelative</code>. This option should enable the user to declare tween values as relative to existing 
	 * values instead of as fixed absolutes. In the final step, you subclass <code>onUpdate</code> to apply the tween, 
	 * using the <code>_position</code> calculated by this base class:</p>
	 * 
	 * <pre>target[ propName ] = super.correctValue(start + change * _position);</pre>
	 * 
	 * <p>The helper method <code>correctValue</code> is provided in the superclass GoItem, to clean up NaN values 
	 * and apply rounding when <code>useRounding</code> is activated. That's it — events and callbacks are 
	 * dispatched by LinearGo, so subclasses can remain simple.</p>
	 * 
	 * <p>An optional fourth step will make your custom tween compatible with Go managers. To do this, implement 
	 * the IManageable interface. (OverlapMonitor prevents different tween instances from handling the same 
	 * property at once; you can build other managers as well.)</p>
	 * 
	 * {In the game of Go a black or white stone is called a go-ishi.}
	 * 
	 * @author Moses Gunesch
	 */
	public class LinearGo extends GoItem implements IPlayable
	{
		// -== Settable Class Defaults ==-
		
		/**
		 * Class default for the instance property delay.
		 * @default 0
		 * @see #delay
		 */
		public static var defaultDelay : Number = 0;
		
		/**
		 * Class default for the instance property duration.
		 * @default 1
		 * @see #duration
		 */
		public static var defaultDuration : Number = 1;
		
		/**
		 * Class default for the instance property easing.
		 * Note that this property is left null until the first LinearGo
		 * is instantiated, at which time it is set to Quintic.easeOut.
		 * @default fl.motion.easing.Quintic.easeOut
		 * @see #easing
		 */
		public static var defaultEasing:Function;
		
		/**
		 * Normal default easing, this is Quintic.easeOut.
		 * (The two default easings in this class are included because there's
		 * currently no single easing classpath shared between Flash & Flex.)
		 */
		public static function easeOut(t:Number, b:Number, c:Number, d:Number) : Number {
			return c * ((t = t / d - 1) * t * t * t * t + 1) + b;
		};
		
		// -== Class Methods ==-
		
		/**
		 * An alternative default easing with no acceleration.
		 * (The two default easings in this class are included because there's
		 * currently no single easing classpath shared between Flash & Flex.)
		 */
		public static function easeNone(t:Number, b:Number, c:Number, d:Number) : Number {
			return c * t / d + b;
		};
		
		/**
		 * A quick one-time setup command that lets you turn on useFrames mode
		 * as a default for all new tweens and adjust some related settings.
		 * (Note that useFrames mode is normally only used for specialty situations.)
		 * 
		 * @param defaultToFramesMode		Sets an internal default so all new LinearGo instances
		 * 									will be set to use framecounts for their delay and duration. 
		 * 									Also sets GoItem.defaultPulseInterval to enterframe which is 
		 * 									most normal for frame-based updates.
		 * @param useZeroBasedFrameIndex	Normally currentFrame reads 1 on first update, like the Flash
		 * 									timeline starts at Frame 1. Set this option to use a zero-based 
		 * 									index on all tweens instead.
		 * @see #useFrames
		 * @see #currentFrame
		 */
		public static function setupUseFramesMode( defaultToFramesMode: Boolean = true,
								   				   useZeroBasedFrameIndex: Boolean=false):void {
			GoItem.defaultPulseInterval = GoEngine.ENTER_FRAME;
			_useFramesMode = defaultToFramesMode;
			if (useZeroBasedFrameIndex) { _framesBase = 0; }
		}

		// -== Pulic Properties ==-
		
		/**
		 * Number of seconds after start() call that the LinearGo begins processing.
		 * <p>If not set manually, the class default defaultDelay is adopted.</p>
		 * @see #defaultDelay
		 */
		public function get delay():Number {
			return _delay;
		}
		public function set delay(seconds:Number):void {
			if (_state==STOPPED && seconds >= 0) {
				_delay = seconds;
			}
		}
		
		/**
		 * Number of seconds the LinearGo takes to process.
		 * <p>If not set manually, the class default defaultDuration is adopted.</p>
		 * @see #defaultDuration
		 */
		public function get duration():Number {
			return _duration;
		}
		public function set duration(seconds:Number):void {
			if (_state==STOPPED && seconds >= 0) {
				_duration = seconds;
			}
		}
		
		/**
		 * Any standard easing-equation function such as the ones found in
		 * the Flash package fl.motion.easing or the flex package mx.effects.easing.
		 * 
		 * <p>If not set manually, the class default defaultEasing is adopted. An error
		 * is thrown if the function does not follow the typical format. For easings 
		 * that accept more than four parameters use <code>extraEasingParams</code>.
		 * </p>
		 * 
		 * @see #defaultEasing
		 * @see #extraEasingParams
		 */
		public function get easing():Function {
			return _easing;
		}
		public function set easing(type:Function):void {
			if (_state==STOPPED) {
				try {
					if (type(1,1,1,1) is Number) {
						_easing = type;
						return;
					}
				} catch (e:Error) {}
				throw new EasingFormatError();
			}
		}
		
		/**
		 * Additional parameters to pass to easing functions that accept more than four.
		 * @see #easing
		 */
		public function get extraEasingParams() : Array {
			return _extraEaseParams;
		}
		public function set extraEasingParams(params:Array):void {
			if (_state==STOPPED && params is Array && params.length>0) {
				_extraEaseParams = params;
			}
		}
		
		/**
		 * A LinearGoRepeater instance that defines options for repeated 
		 * or back-and-forth cycling animation.
		 * 
		 * <p>You may pass a LinearGoRepeater instance to the constructor's
		 * repeater parameter to set all options at instantiation. The 
		 * repeater's cycles property can be set to an integer, or
		 * to Repeater.INFINITE or 0 to repeat indefinitely, and checked using
		 * <code>linearGo.repeater.currentCycle</code>. LinearGoRepeater's 
		 * <code>reverseOnCycle</code> flag is true by default, which
		 * causes animation to cycle back and forth. In that mode you can
		 * also specify a separate easing function (plus extraEasingParams)
		 * to use for the reverse animation cycle. For example, an easeOut
		 * easing with an easeIn easingOnCycle will produce a more 
		 * natural-looking result. If <code>reverseOnCycle</code> is disabled, 
		 * the animation will repeat its play forward each time.</p>
		 * 
		 * <p>(The repeater property replaces the cycles, easeOnCycle and 
		 * currentCycle parameters in earlier releases of LinearGo).</p>
		 * 
		 * @see org.goasap.managers.LinearGoRepeater LinearGoRepeater
		 */
		public function get repeater(): LinearGoRepeater {
			return _repeater;
		}
		
		/**
		 * When useFrames mode is activated, duration and delay are treated
		 * as update-counts instead of time values.
		 * 
		 * <p>(This mode is normally only used for specialty situations.)</p>
		 * 
		 * <p>Using this feature with a pulseInterval of GoEngine.ENTER_FRAME 
		 * will result in a frame-based update that mimics the behavior of the 
		 * flash timeline. As with the timeline, frame-based tween durations can 
		 * vary based on the host computer's processor load and other factors.</p>
		 * 
		 * <p>The <code>setupUseFramesMode()</code> class method is a much easier
		 * way to use frames in your project, instead of setting this property
		 * on every tween individually.</p>
		 * 
		 * @see #setupUseFramesMode()
		 */
		public function set useFrames(value:Boolean):void {
			if (_state==STOPPED)
				_useFrames = value;
		}
		public function get useFrames():Boolean {
			return _useFrames;
		}
		
		/**
		 * A number between 0 and 1 representing the current tween value.
		 * 
		 * <p>Use this number as a multiplier to apply values to targets 
		 * across time.<p> 
		 * 
		 * <p>Here's an example of what an overridden update method might contain:</p>
		 * <pre>
		 * super.update(currentTime);
		 * target[ propName ] = super.correctValue(startValue + change*_position);
		 * </pre> 
		 * @see #timePosition
		 */
		public function get position():Number {
			return _position;
		}
		
		/**
		 * For time-based tweens, returns a time value which is negative during delay 
		 * then spans the tween duration in positive values, ignoring repeat cycles.
		 * 
		 * <p>In useFrames mode, this getter differs from <code>currentFrame</code>
		 * significantly. Instead of constantly increasing through all cycles as if
		 * tweens were back-to-back in a timeline layer, this method acts more like
		 * a single tween placed at frame 1, with a timeline playhead that scans back 
		 * and forth or loops during cycles. So for a 10-frame tween with a 5-frame 
		 * delay and 2 repeater cycles with reverseOnCycle set to true, this method 
		 * will return values starting at -5, start the animation at 1, play to 10 
		 * then step backward to 1 again.</p>
		 * 
		 * @see #position
		 * @see #currentFrame
		 * @see #duration
		 * @see #delay
		 * @see #setupUseFramesMode()
		 */
		public function get timePosition():Number {
			if (_state==STOPPED)
				return 0;
			var mult:Number = Math.max(0, timeMultiplier);
			if (_useFrames) {
				if (_currentFrame>_framesBase) {
					var cf:uint = _currentFrame-_framesBase;
					if (_repeater.direction==-1) {
						return ((_duration-1) - cf%_duration) + _framesBase;
					}
					return cf%_duration + _framesBase;
				}
				return _currentFrame;
			}
			return ((getTimer()-_startTime) / 1000 / mult);
		}

		/**
		 * Returns the number of updates that have occured since start.
		 * 
		 * <p>This update-count property does not necessarily correspond 
		 * to the actual player framerate, just the instance's pulseInterval.</p>
		 * 
		 * <p>This property is set up to mirror the flash timeline. Imagine a timeline 
		 * layer with a delay being a set of blank frames followed by the tween, 
		 * followed by subsequent cycles as additional tweens: this is the way 
		 * the <code>currentFrame</code> property works. Its first value is 1 by 
		 * default, which can be changed to 0 in <code>setupUseFramesMode()</code>.
		 * This differs significantly from <code>timePosition</code>, which places 
		 * the start of a single instance of the tween at frame 1 and steps its
		 * values from negative during delay then cycling through the single tween.</p> 
		 * 
		 * 
		 * @see #useFrames
		 * @see #setupUseFramesMode()
		 * @see #timePosition
		 */
		public function get currentFrame():uint {
			return _currentFrame;
		}
		
		// -== Protected Properties ==-
		
		/** @private */
		protected static var _useFramesMode : Boolean = false;
		
		/** @private */
		protected static var _framesBase : Number = 1;
		
		/** @private */
		protected var _delay 			: Number;
		
		/** @private */
		protected var _duration 		: Number;
		
		/** @private */
		protected var _tweenDuration	: Number;
		
		/** @private */
		protected var _easing 			: Function;
		
		/** @private */
		protected var _easeParams		: Array;
		
		/** @private */
		protected var _extraEaseParams	: Array;
		
		/** @private */
		protected var _repeater			: LinearGoRepeater;
		
		/** @private */
		protected var _currentEasing	: Function;
		
		/** @private */
		protected var _useFrames		: Boolean;
		
		/** @private */
		protected var _started			: Boolean = false;
		
		/** @private */
		protected var _currentFrame		: int;
		
		/** @private */
		protected var _position			: Number;
		
		/** @private */
		protected var _change			: Number;
		
		/** @private */
		protected var _startTime		: Number;
		
		/** @private */
		protected var _endTime 			: Number;
		
		/** @private */
		protected var _pauseTime 		: Number;
		
		/** @private */
		protected var _callbacks		: Object = new Object(); // In tests, creating this object up front is more efficient.
		
		// -== Public Methods ==-
		
		/**
		 * The inputs here are not a convention, subclasses should design
		 * their own constructors appropriate to usage. They are provided
		 * here primarily as a convenience for subclasses. However, do not
		 * omit calling super() from subclass constructors: LinearGo's
		 * constructor sets and validates class defaults and sets up the
		 * repeater instance.
		 */
		public function LinearGo(	delay	 			: Number=NaN,
									duration 			: Number=NaN,
									easing 				: Function=null,
									extraEasingParams	: Array=null,
									repeater			: LinearGoRepeater=null,
									useRelative			: Boolean=false,
									useRounding			: Boolean=false,
									useFrames			: Boolean=false,
									pulseInterval		: Number=NaN ) {
			// validate & set class defaults first
			if (isNaN(defaultDelay))
				defaultDelay = 0;
			if (isNaN(defaultDuration))
				defaultDuration = 1;
			try { this.easing = defaultEasing; }
			catch (e1:EasingFormatError) { defaultEasing = easeOut; }
			// set params
			if (!isNaN(delay)) _delay = delay;
			else _delay = defaultDelay;
			if (!isNaN(duration)) _duration = duration;
			else _duration = defaultDuration;
			try { this.easing = easing; }
			catch (e2:EasingFormatError) {
				if (easing!=null) { throw e2; } // user passed invalid easing function
				this.easing = defaultEasing;
			}
			if (extraEasingParams) _extraEaseParams = extraEasingParams;
			if (useRelative) this.useRelative = true;
			if (useRounding) this.useRounding = true;
			_useFrames = (useFrames || _useFramesMode);
			if (!isNaN(pulseInterval)) _pulse = pulseInterval;
			if (repeater!=null) _repeater = repeater; // repeater setup makes super() call important for all subclasses.
			else _repeater = new LinearGoRepeater(); 
			_repeater.setParent(this);
		}
		
		/**
		 * Starts play for this LinearGo instance using GoEngine.
		 * 
		 * <p>CONVENTION ALERT: If <code>useRelative</code> is true, calculate tween values
		 * relative to the target object's existing value as in the example below.</p>
		 * 
		 * <p>Most typically you should also store the tween's start and change values
		 * for later use in <code>onUpdate</code>.</p>
		 * 
		 * <pre>
		 * protected var _target : DisplayObject;
		 * protected var _width : Number;
		 * protected var _changeWidth : Number;
		 * 
		 * public function start():Boolean 
		 * {
		 *     if (!_target || !_width || isNaN(_width))
		 *         return false;
		 * 
		 *     _startWidth = _target.width;
		 * 
		 *     if (useRelative) {
		 *         _changeWidth = _width;
		 *     } else {
		 *         _changeWidth = (_width - _startWidth);
		 *     }
		 *     
		 *     return (super.start());
		 * }
		 * </pre>
		 * 
		 * @return Successful addition of the item to GoEngine
		 * 
		 * @see GoItem#useRelative
		 * @see #onUpdate()
		 */
		public function start() : Boolean {
			stop(); // does nothing if already stopped.
			if (GoEngine.addItem(this)==false)
				return false;
			reset();
			_state = (_delay > 0 ? PLAYING_DELAY : PLAYING); // has to be set here since delay is not included in PlayableBase.
			// note: start event is dispatched on the first update cycle for tighter cross-item syncing.
			return true;
		}
		
		/**
		 * Ends play for this LinearGo instance and dispatches a GoEvent.STOP
		 * event if the tween is incomplete. This method does not typically 
		 * require subclassing.
		 * 
		 * @return Successful removal of the item from GoEngine
		 */
		public function stop() : Boolean {
			if (_state==STOPPED || GoEngine.removeItem(this)==false)
				return false;
			_state = STOPPED;
			var completed:Boolean = (_easeParams!=null && _position==_easeParams[1]+_change);
			reset();
			if (!completed) // otherwise a COMPLETE event was dispatched.
				dispatch( GoEvent.STOP );
			return true;
		}

		/**
		 * Pauses play (including delay) for this LinearGo instance.
		 * This method does not typically require subclassing.
		 * 
		 * @return Success
		 * @see #resume()
		 * @see org.goasap.GoEngine#setPaused GoEngine.setPaused()
		 */
		public function pause() : Boolean {
			if (_state==STOPPED || _state==PAUSED)
				return false;
			_state = PAUSED;
			_pauseTime = (_useFrames ? _currentFrame : getTimer()); // This causes update() to skip processing.
			dispatch(GoEvent.PAUSE);
			return true;
		}
		
		/**
		 * Resumes previously paused play, including delay.
		 * This method does not typically require subclassing.
		 * 
		 * @return Success
		 * @see #pause()
		 * @see org.goasap.GoEngine#setPaused GoEngine.setPaused()
		 */
		public function resume() : Boolean {
			if (_state != PAUSED)
				return false;
			var currentTime:Number = (_useFrames ? _currentFrame : getTimer());
			setup(currentTime - (_pauseTime - _startTime)); 
			_pauseTime = NaN;
			_state = (_startTime > currentTime ? PLAYING_DELAY : PLAYING);
			dispatch(GoEvent.RESUME);
			return true;
		}
		
		/**
		 * Skips to a point in the tween's duration and plays, from any state. 
		 * This method does not typically require subclassing.
		 * 
		 * <p>If GoItem.timeMultiplier is set to a custom value, you should still pass a 
		 * seconds value based on the tween's real duration setting.</p>
		 * 
		 * @param time		Seconds or frames to jump to across all cycles, where 0 (or 1 in useFramesMode)
		 * 					represents tween start, numbers greater than duration represent higher repeat cycles,
		 * 					and negative numbers represent a new delay to play before tween start.
		 * @return Success
		 * @see #timePosition
		 */
		public function skipTo(time : Number) : Boolean 
		{
			if (_state==STOPPED) {
				if (start()==false) 
					return false;
			}
			if (isNaN(time)) { time = 0; }
			var mult:Number = Math.max(0, timeMultiplier) * (_useFrames ? 1 : 1000);
			var startTime:Number;
			var currentTime:Number;
			if (time < _framesBase) { // Negative value: rewind and add a new delay.
				_repeater.reset();
				if (_position>0) { skipTo(_framesBase); } // skips to start so new pause occurs in starting position
			}
			else {
				time = _repeater.skipTo(_duration, time-_framesBase); // sets cycles and returns new position
			}
			if (_useFrames) {
				startTime = _framesBase;
				currentTime = _currentFrame = Math.round(time*mult);
			}
			else {
				currentTime = getTimer();
				startTime = (currentTime - (time * mult)); // skipTo operation is performed by altering the tween's start & end times.
			}
			setup(startTime);
			_state = (_startTime > currentTime ? PLAYING_DELAY : PLAYING);
			update(currentTime); // sets _position
			return true;
		}
		
		/**
		 * An alternative to subscribing to events is to store callbacks. You can 
		 * associate any number of callbacks with the primary GoEvent types START,
		 * UPDATE, COMPLETE, and STOP (only fired if the tween is stopped before it 
		 * completes).
		 * 
		 * <p>
		 * Note that there is little difference between using callbacks and events.
		 * Both are common techniques used in many various modern tweening APIs. Callbacks 
		 * are slightly faster, but this won't normally be noticeable unless thousands of 
		 * tweens are being run at once.
		 * </p>
		 * 
		 * @param closure	A reference to a callback function
		 * @param type		Any GoEvent type constant, the default is COMPLETE.
		 * @see #removeCallback
		 * @see org.goasap.events.GoEvent GoEvent
		 */
		public function addCallback(closure : Function, type : String=GoEvent.COMPLETE):void {
			if (!_callbacks[ type ]) 
				_callbacks[ type ] = new Array();
			var a:Array = (_callbacks[ type ] as Array);
			if (a.indexOf(closure)==-1)
				a.push(closure);
		}
		
		/**
		 * Removes a method closure previously stored using addCallback.
		 * 
		 * @param closure	A reference to a function
		 * @param type		A GoEvent constant, default is COMPLETE.
		 * @see #addCallback
		 * @see org.goasap.events.GoEvent GoEvent
		 */
		public function removeCallback(closure : Function, type : String=GoEvent.COMPLETE):void {
			var a:Array = (_callbacks[ type ] as Array);
			if (a) 
				while (a.indexOf(closure)>-1)
					a.splice(a.indexOf(closure), 1);
		}
		
		/**
		 * Performs tween calculations on GoEngine pulse.
		 * 
		 * <p>Subclass <code>onUpdate</code> instead of this method.
		 * 
		 * @param currentTime	Clock time for the current block of updates.
		 * @see #onUpdate()
		 */
		override public function update(currentTime:Number) : void 
		{
			if (_state==PAUSED)
				return;
			
			_currentFrame ++;
			if (_useFrames) 
				currentTime = _currentFrame;
			
			if (isNaN(_startTime))		// setup() must be called once prior to tween's 1st update. 
				setup(currentTime);		// This is done here, not in start, for tighter syncing of items.
			
			if (_startTime > currentTime) 
				return; // still PLAYING_DELAY
			
			// (1.) Set _position and determine primary update type.
			var type:String = GoEvent.UPDATE;
			if (currentTime < _endTime) { // start, update...
				if (!_started)
					type = GoEvent.START;
				_easeParams[0] = (currentTime - _startTime);
				_position = _currentEasing.apply(null, _easeParams); // update position using easing function
			}
			else { // complete, cycle...
				_position = _easeParams[1] + _change; // set absolute 1 or 0 position at end of cycle
				type = (_repeater.hasNext() ? GoEvent.CYCLE : GoEvent.COMPLETE);
			}
			
			// (2.) Run onUpdate() passing the primary update type, then
			// (3.) dispatch up to three events in correct order.
			onUpdate(type);
			if (!_started) {
				_state = PLAYING;
				_started = true;
				dispatch(GoEvent.START);
			}
			dispatch(GoEvent.UPDATE);
			if (type==GoEvent.COMPLETE) {
				stop();
				dispatch(GoEvent.COMPLETE);
			}
			else if (type==GoEvent.CYCLE) {
				_repeater.next();
				dispatch(GoEvent.CYCLE);
				_startTime = NaN; // causes setup() to be called again on next update to prep next cycle.
			}
		}
		
		// -== Protected Methods ==-
		
		/**
		 * Subclass this method (instead of the update method) for simplicity. 
		 * 
		 * <p>Use this method to manipulate targets based on the current _position 
		 * setting, which is a 0-1 multiplier precalculated to the tween's position
		 * based on its easing style and the current time in the tween.</p> 
		 * 
		 * <p>CONVENTION ALERT: To implement the Go convention <code>useRounding</code>,
		 * always call GoItem's <code>correctValue()</code> method on each calculated 
		 * tween value before you apply it to a target. This corrects NaN to 0 and 
		 * rounds the value if <code>useRounding</code> is true.</p>
		 * 
		 * Example:
		 * <pre>
		 * override protected function onUpdate(type:String):void
		 * {
		 *     target[ propName ] = super.correctValue(startValue + change*_position);
		 * }
		 * </pre>
		 * 
		 * @param type	A constant from the class GoEvent: START, UPDATE, CYCLE, or COMPLETE.
		 * @see GoItem#correctValue()
		 * @see GoItem#useRounding
		 * @see #update()
		 */
		protected function onUpdate(type : String) : void 
		{
			// Subclass this method and start to implement your tween class.
		}
		
		/**
		 * @private
		 * Internal setup routine used by start() and other methods.
		 * 
		 * @param time			Tween start time based on getTimer
		 */
		protected function setup(startTime : Number) : void 
		{
			_startTime = startTime;
			var mult:Number = Math.max(0, timeMultiplier) * (_useFrames ? 1 : 1000);
			_tweenDuration = (_useFrames ? Math.round(_duration * mult)-1 : (_duration * mult));
			_endTime = _startTime + _tweenDuration;
			if (!_started) {
				var d:Number = (_useFrames ? Math.round(_delay * mult) : (_delay * mult));
				_startTime += d;
				_endTime += d;
			}
			// Set up a tween cycle: _currentEasing, _change, _position, and _easeParams. 
			// Be sure _repeater is updated before this call so the next cycle gets set up.
			var useCycleEase:Boolean = _repeater.currentCycleHasEasing;  
			_currentEasing = (useCycleEase ? _repeater.easingOnCycle : _easing);
			var extras:Array = (useCycleEase ? _repeater.extraEasingParams : _extraEaseParams);
			_change = _repeater.direction;
			_position = (_repeater.direction==-1 ? 1 : 0);
			_easeParams = new Array(0, _position, _change, _tweenDuration); // stored to reduce runtime object-creation
			if (extras) _easeParams = _easeParams.concat(extras);
		}
		
		/**
		 * @private
		 * Internal, dispatches events and executes callbacks of any pre-verified type.
		 *  
		 * @param type	Verified in addCallback, not in this method.
		 * @see #org.goasap.events.GoEvent GoEvent
		 */
		protected function dispatch(type:String):void 
		{
			var a:Array = (_callbacks[ type ] as Array);
			if (a)
				for each (var callback:Function in a)
					callback();
			if (hasEventListener(type))
				dispatchEvent(new GoEvent( type ));
		}
		
		/**
		 * @private
		 */
		protected function reset() : void {
			_position = 0;
			_change = 1;
			_repeater.reset();
			_currentFrame = _framesBase-1;
			_currentEasing = _easing;
			_easeParams = null;
			_started = false;
			_pauseTime = NaN;
			_startTime = NaN;
		}
	}
}