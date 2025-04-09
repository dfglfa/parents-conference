function checkPasswordStrength(password) {
  const minLength = 8;
  const hasDigit = /\d/;
  const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/;

  if (password.length < minLength) {
    return "Das Passwort muss mindestens 8 Zeichen lang sein.";
  }
  if (!hasDigit.test(password)) {
    return "Das Passwort muss mindestens eine Ziffer enthalten.";
  }
  if (!hasSpecialChar.test(password)) {
    return "Das Passwort muss mindestens ein Sonderzeichen enthalten.";
  };
}

function submitPassword() {
    const oldPassword = $("#passwordOld").val();
    const newPassword = $("#passwordNew").val();
    const newPasswordConfirm = $("#passwordNew2").val();  

    let problem = "";
    if (newPassword !== newPasswordConfirm) {
      problem = "Die Passwörter stimmen nicht überein!";
    } else {
      problem = checkPasswordStrength(newPassword);
    }

    const feedback = $("#passwordFeedback");
    if (problem) {
      feedback.html(problem);
      return;
    } else {
      feedback.html("");
    }

    $.ajax({
      url: "controller.php",
      type: "POST",
      data: {action: "changePassword", oldPassword, newPassword},
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          showMessage(feedback, "success", "Das Passwort wurde geändert!");
          $("input").val("");
        } else if (data.indexOf("incorrect") > -1) {
          showMessage(feedback, "danger", "Das aktuelle Passwort ist nicht korrekt!");
        } else {
          showMessage(feedback, "danger", "Das Passwort konnte nicht geändert werden! (" + data + ")");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(feedback, "danger", "Das Passwort konnte nicht geändert werden!");
      },
    });  

  return true;
}
