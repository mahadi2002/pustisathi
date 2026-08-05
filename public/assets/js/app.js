'use strict';

/* BMI/BMR calculator — client-side only, nothing sent to the server
   (01-BUILD-SPEC.md section 3: "BMI/BMR calculator (no save)"). */
(function () {
  var form = document.getElementById('bmi-form');
  if (!form) {
    return;
  }

  var BMI_LABELS = [
    [18.5, 'Underweight'],
    [23, 'Normal'],
    [25, 'Overweight'],
    [Infinity, 'Obese'],
  ];

  function bmiLabel(bmi) {
    for (var i = 0; i < BMI_LABELS.length; i++) {
      if (bmi < BMI_LABELS[i][0]) {
        return BMI_LABELS[i][1];
      }
    }
    return 'Obese';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var height = parseFloat(document.getElementById('height').value);
    var weight = parseFloat(document.getElementById('weight').value);
    var age = parseInt(document.getElementById('age').value, 10);
    var sex = document.getElementById('sex').value;
    var activity = parseFloat(document.getElementById('activity').value);

    if (!height || !weight || !age) {
      return;
    }

    var heightM = height / 100;
    var bmi = weight / (heightM * heightM);

    // Mifflin-St Jeor
    var bmr = sex === 'male'
      ? 10 * weight + 6.25 * height - 5 * age + 5
      : 10 * weight + 6.25 * height - 5 * age - 161;

    var tdee = bmr * activity;

    document.getElementById('bmi-value').textContent = bmi.toFixed(1);
    document.getElementById('bmi-label').textContent = bmiLabel(bmi);
    document.getElementById('bmr-value').textContent = Math.round(tdee).toString();
    document.getElementById('bmi-result').hidden = false;
  });
})();
