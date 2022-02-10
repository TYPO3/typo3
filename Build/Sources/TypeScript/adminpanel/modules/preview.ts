namespace TYPO3 {
  export class Preview {
    private readonly dateField: HTMLInputElement = null;
    private readonly timeField: HTMLInputElement = null;
    private readonly targetField: HTMLInputElement = null;
    private readonly toggleField: HTMLInputElement = null;

    /**
     * Initialize date and time fields of preview
     *
     * PHP / backend side always uses UTC timestamps (for generating time based previews and access time checks)
     * Date and Time fields are HTML5 input fields, combined they update the "targetfield" always containing a PHP
     * compatible (seconds-based) timestamp
     */
    constructor() {
      this.dateField = <HTMLInputElement>document.getElementById('preview_simulateDate-date-hr');
      this.timeField = <HTMLInputElement>document.getElementById('preview_simulateDate-time-hr');
      this.targetField = <HTMLInputElement>document.getElementById(this.dateField.dataset.bsTarget);
      this.toggleField = <HTMLInputElement>document.getElementById('typo3-adminPanel-simulate-date-toggle');

      if (this.targetField.value) {
        const initialDate = new Date(parseInt(this.targetField.value, 10) * 1000);
        this.dateField.valueAsDate = initialDate;
        this.timeField.valueAsDate = initialDate;
      }

      this.toggleField.addEventListener('change', this.toggleDisplay)
      this.dateField.addEventListener('change', this.updateDateField);
      this.timeField.addEventListener('change', this.updateDateField);
    }

    private toggleDisplay = (): void => {
      let toggleVal = this.toggleField.checked;
      let groupElement = <HTMLDivElement>document.getElementById('typo3-adminPanel-preview_simulateDate');
      if (toggleVal) {
        groupElement.classList.remove('typo3-adminPanel-group-disable')
        this.dateField.disabled = false
        this.timeField.disabled = false
        this.updateDateField()
      } else {
        groupElement.classList.add('typo3-adminPanel-group-disable')
        this.dateField.disabled = true
        this.timeField.disabled = true
        this.targetField.value = ''
      }
    }

    private updateDateField = (): void => {
      let dateVal = this.dateField.value;
      let timeVal = this.timeField.value;
      if (!dateVal && timeVal) {
        let tempDate = new Date();
        dateVal = tempDate.getFullYear() + '-' + (tempDate.getMonth() + 1) + '-' + tempDate.getDate();
      }
      if (dateVal && !timeVal) {
        timeVal =  '00:00';
      }

      if (!dateVal && !timeVal) {
        this.targetField.value = '';
      } else {
        const stringDate = dateVal + ' ' + timeVal;
        const date = new Date(stringDate);

        this.targetField.value = (date.valueOf() / 1000).toString();
      }
    }
  }
}

((): void => {
  window.addEventListener(
    'load',
    () => new TYPO3.Preview(),
    false,
  );
})();
