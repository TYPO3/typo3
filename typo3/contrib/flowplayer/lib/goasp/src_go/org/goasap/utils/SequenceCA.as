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
package org.goasap.utils {
	import flash.events.Event;
	
	import org.goasap.events.GoEvent;
	import org.goasap.events.SequenceEvent;
	import org.goasap.interfaces.IPlayable;	
	
	/**
	 * Sequence with "Custom Advance" options, in which steps can specify when they should advance.
	 * 
	 * <p>This class works like Sequence but uses the special class SequenceStepCA for its steps.
	 * SequenceStepCA has a property called <code>advance</code>. When steps advance before animation 
	 * finishes, the trailing steps are tracked so that the SequenceCA doesn't dispatch its COMPLETE
	 * event until all activity has completed.</p>
	 * 
	 * <p>Any step's advance property can be set to an instance of OnDurationComplete, OnPlayableComplete,
	 * OnEventComplete or OnConditionTrue. Each of those classes defines its own parameters and rules for
	 * when the advance occurs. For example, using OnPlayableComplete a sequence can advance after one 
	 * particular item in the step finishes, without needing to wait for all the other ones in that group
	 * to complete.</p>
	 * 
	 * <p>Additionally, you can create your own custom advance types by subclassing the SequenceAdvance 
	 * base class.</p>
	 * 
	 * @see SequenceStepCA
	 * @see org.goasap.utils.customadvance.OnConditionTrue OnConditionTrue
	 * @see org.goasap.utils.customadvance.OnDurationComplete OnDurationComplete
	 * @see org.goasap.utils.customadvance.OnEventComplete OnEventComplete
	 * @see org.goasap.utils.customadvance.OnPlayableComplete OnPlayableComplete
	 * @see org.goasap.utils.customadvance.SequenceAdvance SequenceAdvance
	 * @see Sequence
	 * @see SequenceBase
	 * 
	 * @author Moses Gunesch
	 */
	public class SequenceCA extends SequenceBase {
		
		// -== Public Properties ==-
		
		// Also in super:
		// length : uint   [Read-only.]
		// playIndex : int [Read-only.]
		// steps : Array
		// start() : Boolean
		// stop() : Boolean
		// pause() : Boolean
		// resume() : Boolean
		// skipTo(index:Number) : Boolean
		
		/**
		 * Returns the currently-playing SequenceStepCA.
		 * @return The currently-playing SequenceStepCA.
		 * @see #getStepAt()
		 * @see #getStepByID()
		 * @see #steps
		 * @see #lastStep
		 */
		public function get currentStep() : SequenceStepCA {
			return (super._getCurrentStep());
		}
		
		/**
		 * Returns the final SequenceStepCA in the current sequence.
		 * @return The final SequenceStepCA in the current sequence.
		 * @see #getStepAt()
		 * @see #getStepByID()
		 * @see #steps
		 * @see #currentStep
		 */
		public function get lastStep() : SequenceStepCA {
			return (super._getLastStep());
		}
		
		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		protected var _trailingSteps : SequenceStep;
		
		// -== Public Methods ==-
		
		/**
		 * Constructor.
		 * 
		 * @param items		Any number of IPlayable instances (e.g. LinearGo, PlayableGroup,
		 * 					SequenceStepCA) as separate arguments, or a single array of them.
		 */
		public function SequenceCA(...items) {
			super((items[ 0 ] is Array) ? items[ 0 ] : items);
		}
		
		/**
		 * Retrieves any SequenceStepCA from the steps array.
		 * @param index		An array index starting at 0.
		 * @return			The SequenceStepCA instance at this index.
		 * @see #getStepByID()
		 */
		public function getStepAt(index:int) : SequenceStepCA {
			return (super._getStepAt(index) as SequenceStepCA);
		}
		
		/**
		 * Locates a step with the specified playableID. To search within a step for a
		 * child by playableID, use the step instance's <code>getChildByID</code> method.
		 *  
		 * @param playableID	The step instance's playableID to search for.
		 * @return				The SequenceStepCA with the matching playableID.
		 */
		public function getStepByID(playableID:*) : SequenceStepCA {
			return (super._getStepByID(playableID) as SequenceStepCA);
		}
		
		/**
		 * Adds a single IPlayable instance (e.g. LinearGo, PlayableGroup, SequenceStepCA) 
		 * to the end of the steps array, or optionally adds the instance into the last 
		 * SequenceStepCA instead of adding it as a new step.
		 * 
		 * <p>To remove a step use the <code>removeStepAt</code> method.</p>
		 * 
		 * @param item			The playable item to add to the sequence. Note
		 * 						that when new steps are added, any IPlayable
		 * 						instance of a type other than SequenceStepCA is 
		 * 						automatically wrapped in a new SequenceStepCA.
		 * 						
		 * @param addToLastStep	If true is passed the item is added to the last
		 * 						existing SequenceStepCA in the steps array. This
		 * 						option should be used with individual items that
		 * 						you want added as children to the SequenceStepCA.
		 * 						If there are no steps yet this option ignored and
		 * 						a new step is created.
		 * 						
		 * @return New length of the steps array.
		 */
		public function addStep(item:IPlayable, addToLastStep:Boolean=false): int {
			return (super._addStep(item, addToLastStep, SequenceStepCA));
		}
		
		/**
		 * Adds a single IPlayable instance (e.g. LinearGo, PlayableGroup, SequenceStepCA) 
		 * at a specific index in the steps array. Calling this method stops any sequence 
		 * play currently in progress.
		 * 
		 * @param item		The playable item to splice into the sequence.
		 * 
		 * @param index		Position in the array starting at 0, or a negative 
		 * 					index like Array.splice.
		 * 					
		 * @return 			New length of the steps array.
		 */
		public function addStepAt(item:IPlayable, index:int): int {
			return (super._addStepAt(index, item, SequenceStepCA));
		}

		/**
		 * Removes and returns the SequenceStepCA at a specific index from the steps 
		 * array. Calling this method stops any sequence play currently in progress.
		 * 
		 * @param index		Position in the array starting at 0, or a negative 
		 * 					index like Array.splice.
		 * 					
		 * @return 			The SequenceStepCA instance removed from the steps array.
		 */
		public function removeStepAt(index:int): SequenceStepCA {
			return (super._removeStepAt(index) as SequenceStepCA);
		}
				
		// -== IPlayable implementation ==-

		/**
		 * Begins a sequence.
		 * 
		 * <p>If the group is active when this method is called, a <code>stop</code> call 
		 * is automated which will result in a GoEvent.STOP event being dispatched.</p>
		 * 
		 * @return Returns true unless there are no steps in the sequence.
		 */
		override public function start() : Boolean {
			return super.start();
		}
		
		/**
		 * Stops all activity and dispatches a GoEvent.STOP event.
		 * 
		 * @return Returns true unless sequence was already stopped.
		 */
		override public function stop() : Boolean {
			if (super.stop()==false)
				return false;
			initTrailingSteps(false);
			return true;
		}
		
		/**
		 * Pauses sequence play.
		 * 
		 * @return  Returns true unless sequence was unable to pause any children.
		 */
		override public function pause() : Boolean {
			var success:Boolean = super.pause();
			if (_trailingSteps!=null) {
				_trailingSteps.pause();
				if (_trailingSteps.state==PAUSED) {
					_state = PAUSED;
					success = true;
				}
			}
			return success;
		}
		
		/**
		 * Resumes previously-paused sequence play.
		 * 
		 * @return  Returns true unless sequence was unable to resume any children.
		 */
		override public function resume() : Boolean {
			var success:Boolean = super.resume();
			if (_trailingSteps!=null) {
				_trailingSteps.resume();
				if (_trailingSteps.state==PLAYING) {
					_state = PLAYING;
					success = true;
				}
			}
			return success;
		}
		
		/**
		 * Stops current activity and skips to another step by sequence index.
		 * 
		 * @return Always returns true since the index is normalized to the sequence.
		 */
		override public function skipTo(index : Number) : Boolean {
			initTrailingSteps(false);
			return super.skipTo(index);
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 * Internal handler for item completion.
		 * @param event		SequenceEvent dispatched by child item. 
		 */
		override protected function onStepEvent(event : Event) : void {
			// A stop() call to the sequence results in step dispatching STOP, which would recurse here.
			if (_state==STOPPED)
				return;
			// trailing item
			if (_trailingSteps!=null && event.target==_trailingSteps && event.type==SequenceEvent.ADVANCE) {
				initTrailingSteps(false);
				if (_steps.length-_index==1) {
					if (lastStep.state==STOPPED) {
						// A completed sequence was waiting for trailing steps to finish.
						// Otherwise, trailing items have finished before sequence ended so no action should be taken.
						complete();
					}
					else {
						// Special case where advance already fired but trailing steps have all completed: use COMPLETE
						lastStep.addEventListener(GoEvent.COMPLETE, onStepEvent);
					}
				}
				return;
			}
			
			// Finishes special case in trailing item block. Also, returns out if we're waiting 
			if (lastStep.hasEventListener(GoEvent.COMPLETE)) {
				if (event.type==GoEvent.COMPLETE) {
					initTrailingSteps(false); // _trailingSteps is null, this is to remove the COMPLETE listener.
					complete();
				}
				return;
			}
			
			super.onStepEvent(event);
		}
						
		/**
		 * @private
		 * Internal handler for step advance.
		 */
		override protected function advance() : void {
			if (currentStep.listenerCount > 0) {
				initTrailingSteps(true);
				var isFirstItem:Boolean = (_trailingSteps.children.length==0);
				_trailingSteps.addChild(currentStep, isFirstItem); // 2nd param is adoptChildState flag: avoids a start call on the group.
			}
			super.advance();
		}
		
		/**
		 * @private
		 * Internal handler for group completion.
		 */
		override protected function complete() : void {
			if (_trailingSteps==null) {
				super.complete();
			}
		}
		
		/**
		 * @private
		 * Internal setup for tracking items that are continuing to run after a custom advance.
		 * @param active	Whether to create or destroy the trailing-steps group.
		 */
		protected function initTrailingSteps(active:Boolean):void {
			if (_trailingSteps==null && active) {
				_trailingSteps = new SequenceStep();
				_trailingSteps.playableID += "(_trailingSteps for sequence:"+playableID+")";
				_trailingSteps.addEventListener(SequenceEvent.ADVANCE, onStepEvent);
			}
			else if (!active) {
				lastStep.removeEventListener(GoEvent.COMPLETE, onStepEvent); // Remove special case set in onStepEvent.
				if (_trailingSteps!=null) {
					_trailingSteps.removeEventListener(SequenceEvent.ADVANCE, onStepEvent);
					_trailingSteps.stop();
					_trailingSteps = null;
				}
			}
		}
	}
}
