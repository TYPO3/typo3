function initializePreviewModule() {
  var dateField = document.getElementById('preview_simulateDate-date-hr');
  var timeField = document.getElementById('preview_simulateDate-time-hr');
  var targetField = document.getElementById(dateField.dataset.target);
  if (targetField.value) {
    var initialDate = new Date(parseInt(targetField.value, 10) * 1000);
    dateField.valueAsDate = initialDate;
    timeField.valueAsDate = initialDate;
  }

  var updateDateField = function () {
    var dateVal = document.getElementById('preview_simulateDate-date-hr').value;
    var timeVal = document.getElementById('preview_simulateDate-time-hr').value;
    if (!dateVal && timeVal) {
      var tempDate = new Date();
      dateVal = tempDate.getFullYear() + "-" + (tempDate.getMonth() + 1) + "-" + tempDate.getDate();
    }
    if (dateVal && !timeVal) {
      timeVal = "00:00";
    }

    if (!dateVal && !timeVal) {
      targetField.value = "";
    } else {
      var stringDate = dateVal + " " + timeVal;
      var date = new Date(stringDate);
      targetField.value = (date.valueOf() / 1000).toString();
    }
  };
  dateField.addEventListener('change', updateDateField);
  timeField.addEventListener('change', updateDateField);
}

window.addEventListener('load', initializePreviewModule, false);
