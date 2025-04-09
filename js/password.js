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
      problem = "Die Passwörter stimmen nicht überein! " + newPassword + " " + newPasswordConfirm;
    } else {
      problem = checkPasswordStrength(newPassword);
    }

    if (problem) {
      $("#passwordFeedback").html(problem);
      return;
    } else {
      $("#passwordFeedback").html("");
    }

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        var message = $("#message");
        if (data.indexOf("success") > -1) {
          $("#attendance").load("viewController.php?action=attendance");

          showMessage(message, "success", "Die Anwesenheit wurde erfolgreich geändert!");
          loadTimeTable();
        } else {
          showMessage(message, "danger", "Die Anwesenheit konnte nicht geändert werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Die Anwesenheit konnte nicht geändert werden!");
      },
    });  

  return true;
}
