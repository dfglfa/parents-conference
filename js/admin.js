$(document).ready(function () {
  // Preload some data
  prepareCreateEventForm();
  loadAllAttendances();
  loadConnectedUsersForm();
  loadChangeUserForm("createUser");
  loadAllConnections();

  // Make accordion sections fetch data on expand
  $("#accordion").on("show.bs.collapse", function (e) {
    var panelId = $(e.target).attr("id");

    switch (panelId) {
      case "upload":
        updateUploadInfos();
        break;
      case "planConference":
        prepareCreateEventForm();
        break;
      case "conferenceOverview":
        displayActiveEvent();
        break;
      case "attendances":
        loadAllAttendances();
        break;
      case "userManagement":
        loadChangeUserForm("createUser");
        break;
      case "userConnections":
        loadConnectedUsersForm();
        loadAllConnections();
        break;
      case "mailTemplates":
        addMailTemplateSelectListener();
        break;
      case "textTemplates":
        addTextTemplateSelectListener();
        break;
      case "passwords":
        preparePasswordForm();
        break;
    }
  });

  $(document).on("click", "#btn-create-event", function () {
    validateForm();
    var createEventForm = $("#createEventForm");
    if (createEventForm.valid()) {
      createEventForm.submit(function (e) {
        var postData = $(this).serializeArray();
        var setActive = $('input[name="setActive[]"]:checked').length > 0;
        postData = postData.concat({ name: "action", value: "createEvent" });

        if (setActive) {
          postData = postData.concat({ name: "setActive", value: "true" });
        } else {
          postData = postData.concat({ name: "setActive", value: "false" });
        }

        var formURL = "controller.php";
        $.ajax({
          url: formURL,
          type: "POST",
          data: postData,
          success: function (data, textStatus, jqXHR) {
            var message = $("#createEventMessage");
            if (data.indexOf("success") > -1) {
              showMessage(message, "success", "Der Elternsprechtag wurde erfolgreich angelegt!");
              loadChangeEventsForm();
              displayActiveEvent();
            } else {
              showMessage(message, "danger", "Der Elternsprechtag konnte nicht angelegt werden!");
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            showMessage(message, "danger", "Der Elternsprechtag konnte nicht angelegt werden!");
          },
        });
        e.preventDefault();
        createEventForm.unbind("submit");
      });
    }

    return true;
  });

  $("input[type=radio][name=changeUserType]").change(function () {
    var type = this.value;
    loadChangeUserForm(type);
  });

  $("#inputUploadType").change(function () {
    updateUploadInfos();
  });
});

function updateUploadInfos() {
  var selectedType = $("select#inputUploadType option:checked").val();

  var allowedFileTypes = $("#allowed-file-types");
  var uploadDialog = $("#input-file");
  if (["teacher", "student"].indexOf(selectedType) > -1) {
    allowedFileTypes.html("Es sind nur CSV Dateien erlaubt.");
    uploadDialog.attr("accept", ".csv");
  } else if (selectedType === "logo") {
    allowedFileTypes.html("Es sind nur PNG-Dateien erlaubt.");
    uploadDialog.attr("accept", ".png");
  } else if (selectedType === "map") {
    allowedFileTypes.html("Es sind nur PNG-Dateien erlaubt.");
    uploadDialog.attr("accept", ".png");
  }

  $("#templateDownloadAlertContainer").load("viewController.php?action=templateDownloadAlert&type=" + selectedType);
}

function loadChangeUserForm(type) {
  $("#changeUserForm").load("viewController.php?action=" + type, function () {
    if (type == "changeUser") {
      fillUserEditFields();
    }
  });
}

function validateForm() {
  $("#createEventForm").validate({
    rules: {
      name: {
        minlength: 3,
        required: true,
      },
      date: {
        required: true,
      },
      beginTime: {
        required: true,
      },
      endTime: {
        required: true,
      },
      slotDuration: {
        required: true,
      },
    },
    messages: {
      name: "Gib einen Namen für den Elternsprechtag ein!",
      date: "Gib ein Datum ein!",
      beginTime: "Gib eine Startzeit ein!",
      endTime: "Gib eine Endzeit ein!",
      slotDuration: "Gib eine Dauer für eine Einheit ein!",
    },
    highlight: function (element) {
      var id_attr = "#" + $(element).attr("id") + "1";
      $(element).closest(".form-group").removeClass("has-success").addClass("has-error");
      $(id_attr).removeClass("glyphicon-ok").addClass("glyphicon-remove");
    },
    unhighlight: function (element) {
      var id_attr = "#" + $(element).attr("id") + "1";
      $(element).closest(".form-group").removeClass("has-error").addClass("has-success");
      $(id_attr).removeClass("glyphicon-remove").addClass("glyphicon-ok");
    },
    errorElement: "span",
    errorClass: "help-block",
    errorPlacement: function (error, element) {
      if (element.length) {
        error.insertAfter(element);
      } else {
        error.insertAfter(element);
      }
    },
  });
}

$(document).on("click", "#btn-upload-file", function (event) {
  var uploadFileForm = $("#uploadFileForm");
  uploadFileForm.submit(function (e) {
    e.preventDefault();
    uploadFileForm.unbind("submit");

    var message = $("#uploadFileMessage");
    var data = new FormData();
    $.each($("#input-file")[0].files, function (i, file) {
      data.append("file-" + i, file);
    });
    data.append("action", "uploadFile");

    var postData = $(this).serializeArray();
    var uploadType = postData[0].value;
    data.append("uploadType", uploadType);

    var successMessage = "Die Datei wurde erfolgreich hochgeladen!";
    if (uploadType == "teacher") {
      successMessage = "Die Lehrer wurden erfolgreich importiert!";
      if (
        !confirm(
          "WARNUNG!\n\nBeim Import werden die bestehenden Lehrer-Benutzer und bestehenden Elternsprechtage gelöscht! Soll fortgesetzt werden?"
        )
      ) {
        return;
      }
    } else if (uploadType == "student") {
      successMessage = "Die Schüler wurden erfolgreich importiert!";
      if (
        !confirm("WARNUNG!\n\nBeim Import werden die bestehenden Schüler-Benutzer gelöscht! Soll fortgesetzt werden?")
      ) {
        return;
      }
    } else if (uploadType == "logo") {
      setTimeout(() => $("#navLogo").attr("src", "public/logo.png" + "?t=" + new Date().getTime()), 1000);
    } else if (uploadType == "map") {
      successMessage = "Der Lageplan wurde erfolgreich hochgeladen.";
    }

    $("#btn-upload-file").attr("disabled", true).text("Import läuft ...");
    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      data: data,
      cache: false,
      contentType: false,
      processData: false,
      type: "POST",
      success: function (data, textStatus, jqXHR) {
        $("#btn-upload-file").attr("disabled", false).text("Importieren");
        if (data.indexOf("success") > -1) {
          showMessage(message, "success", successMessage);
          if ($.inArray(uploadType, ["teacher", "student"]) > -1) {
            $("#csv-preview").load("viewController.php?action=csvPreview&role=" + uploadType, function () {
              $("#csv-preview").show();
              displayActiveEvent();
              loadChangeEventsForm();
            });
          }
        } else {
          showMessage(message, "danger", data);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#btn-upload-file").attr("disabled", false).text("Importieren");
        showMessage(message, "danger", data);
      },
    });
  });
});

$(document).on("click", "#btn-change-active-event", function (event) {
  var changeEventForm = $("#changeEventForm");
  changeEventForm.submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "setActiveEvent" });
    var message = $("#changeEventMessage");

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          showMessage(message, "success", "Der aktive Elternsprechtag wurde erfolgreich gesetzt!");
          displayActiveEvent();
        } else {
          showMessage(message, "danger", "Der aktive Elternsprechtag konnte nicht gesetzt werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Der aktive Elternsprechtag konnte nicht gesetzt werden!");
      },
    });
    e.preventDefault();
    changeEventForm.unbind("submit");
  });
});

$(document).on("click", "#btn-delete-event", function (event) {
  var changeEventForm = $("#changeEventForm");
  changeEventForm.submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "deleteEvent" });
    var message = $("#changeEventMessage");

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          showMessage(message, "success", "Der Elternsprechtag wurde erfolgreich gelöscht!");
          loadChangeEventsForm();
          displayActiveEvent();
        } else {
          showMessage(message, "danger", "Der Elternsprechtag konnte nicht gelöscht werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Der Elternsprechtag konnte nicht gelöscht werden!");
      },
    });
    e.preventDefault();
    changeEventForm.unbind("submit");
  });
});

function loadChangeEventsForm() {
  var changeEventForm = $("#changeEventForm");
  $.ajax({
    url: "viewController.php?action=getChangeEventForm",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      changeEventForm.html(data);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      changeEventForm.html("<h1>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h1>");
    },
  });
}

$(document).on("click", "#btn-create-user", function (event) {
  var createUserForm = $("#editUsersForm");
  createUserForm.submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "createUser" });
    var message = $("#changeUserMessage");

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          showMessage(message, "success", "Der Benutzer wurde erfolgreich erstellt!");
          displayActiveEvent();
        } else {
          showMessage(message, "danger", data);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Der Benutzer konnte nicht erstellt werden!");
      },
    });
    e.preventDefault();
    createUserForm.unbind("submit");
  });
});

$(document).on("click", "#btn-edit-user", function (event) {
  var editUsersForm = $("#editUsersForm");
  editUsersForm.submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "editUser" });
    var message = $("#changeUserMessage");

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          showMessage(message, "success", "Der Benutzer wurde erfolgreich geändert!");
          displayActiveEvent();
        } else {
          showMessage(message, "danger", "Der Benutzer konnte nicht geändert werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Der Benutzer konnte nicht geändert werden!");
      },
    });
    e.preventDefault();
    editUsersForm.unbind("submit");
  });
});

$(document).on("click", "#btn-delete-user", function (event) {
  var editUsersForm = $("#editUsersForm");
  editUsersForm.submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "deleteUser" });
    var message = $("#changeUserMessage");

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          loadChangeUserForm("changeUser");
          showMessage(message, "success", "Der Benutzer wurde erfolgreich gelöscht!");
          displayActiveEvent();
        } else {
          showMessage(message, "danger", "Der Benutzer konnte nicht gelöscht werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Der Benutzer konnte nicht gelöscht werden!");
      },
    });
    e.preventDefault();
    editUsersForm.unbind("submit");
  });
});

function fillUserEditFields() {
  var userSelect = $("#selectUser").find("option:selected");
  var user = $.parseJSON(userSelect.val());

  $("#inputUserId").val(user.id);
  $("#inputUserName").val(user.userName);
  $("#inputPassword").val("");
  $("#inputFirstName").val(user.firstName);
  $("#inputLastName").val(user.lastName);
  $("#email").val(user.email);
  $("#inputClass").val(user.class);
  $("#inputRoomNumber").val(user.roomNumber);
  $("#inputRoomName").val(user.roomName);
  if (user.absent == 1) {
    $("#inputAbsent").prop("checked", true);
  } else {
    $("#inputAbsent").prop("checked", false);
  }

  var typeSelect = $("#selectType");
  typeSelect.find("option").removeAttr("selected");
  typeSelect.find("option[value='" + user.role + "']").prop("selected", true);
  changeRoomInputVisibility(user.role == "teacher");
}

$(document).on("change", "#selectUser", function (event) {
  fillUserEditFields();
});

$(document).on("change", "#selectType", function (event) {
  var typeSelect = $("#selectType").find("option:selected");
  var type = typeSelect.val();
  changeRoomInputVisibility(type == "teacher");
});

function changeRoomInputVisibility(condition) {
  if (condition) {
    $("#inputRoomNumber-div").removeClass("hidden");
    $("#inputRoomName-div").removeClass("hidden");
    $("#inputAbsent-div").removeClass("hidden");
  } else {
    $("#inputRoomNumber-div").addClass("hidden");
    $("#inputRoomName-div").addClass("hidden");
    $("#inputAbsent-div").addClass("hidden");
  }
}

$(document).on("change", "#selectUserStats", function (event) {
  var userSelect = $("#selectUserStats");
  userSelect.find("option[value='-1']").remove();

  var selectedUser = userSelect.find("option:selected");
  var user = $.parseJSON(selectedUser.val());
  var userId = user.id;

  $("#statistics").load("viewController.php?action=stats&userId=" + userId);
});

$(document).on("click", "#deleteStatisticsForm .btn", function (event) {
  var message = $("#statisticsMessage");
  var id = $(this).attr("id").replace("btn-delete-statistics-for-userId-", "");

  var successMessage = "Die Statistik wurde erfolgreich gelöscht!";
  var errorMessage = "Die Statistik konnte nicht gelöscht werden!";
  var postData;

  var userId = 1;
  if (id === "btn-delete-whole-statistics") {
    postData = $.param({ action: "deleteStats", userId: -1 });
  } else {
    userId = id;
    postData = $.param({ action: "deleteStats", userId: userId });
    successMessage = "Die Statistik für den ausgewählten Benutzer wurde erfolgreich gelöscht!";
  }

  var formURL = "controller.php";
  $.ajax({
    url: formURL,
    type: "POST",
    data: postData,
    success: function (data, textStatus, jqXHR) {
      if (data.indexOf("success") > -1) {
        $("#statistics").load("viewController.php?action=stats&userId=" + userId, function () {
          var newMessage = $("#statisticsMessage");
          showMessage(newMessage, "success", successMessage);
        });
      } else {
        showMessage(message, "danger", errorMessage);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showMessage(message, "danger", errorMessage);
    },
  });
});

$(document).on("click", "#print-panel", function (event) {
  loadTimeTable();
});

function prepareCreateEventForm() {
  $('[data-toggle="tooltip"]').tooltip();
  $("#throttleQuotaSelect").change(() => {
    if ($("#throttleQuotaSelect").val() === "0") {
      $("#throttleDaysSelect").addClass("hidden");
    } else {
      $("#throttleDaysSelect").removeClass("hidden");
    }
  });
  $("#datePickerBookingStart").datetimepicker({
    format: "dd.mm.yyyy hh:ii",
    language: "de",
  });

  $("#datePickerBookingEnd").datetimepicker({
    format: "dd.mm.yyyy hh:ii",
    language: "de",
  });

  $("#individualBreaks div").hide();
  $("#commonBreaks div").hide();

  $("#noBreaks input[value='none']").click(() => {
    $("#individualBreaks div").hide();
    $("#commonBreaks div").hide();
  });

  $("input[value='individual']").click(() => {
    $("#commonBreaks div").hide();
    $("#individualBreaks div").show();
  });

  $("input[value='common']").click(() => {
    $("#commonBreaks div").show();
    $("#individualBreaks div").hide();
  });
}

function loadTimeTable(typeId) {
  var timeTable = $("#adminTimeTable");
  $.ajax({
    url: "viewController.php?action=getAdminTimeTable",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      timeTable.html(data);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      timeTable.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}

function displayAttendance() {
  const teacherSelect = $("#selectTeacher");

  const selectedTeacher = teacherSelect.find("option:selected");

  if (!selectedTeacher || !selectedTeacher.val()) {
    return;
  }

  const teacherId = selectedTeacher.val();
  const eventId = $("#activeEventId").val();

  $("#changeAttendanceTime").load(
    "viewController.php?action=changeAttendance&userId=" + teacherId + "&eventId=" + eventId
  );
}

function displayActiveEvent() {
  var activeEventContainer = $("#activeEventContainer");
  $.ajax({
    url: "viewController.php?action=getActiveEventContainer",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      activeEventContainer.html(data);
      displayAttendance();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      activeEventContainer.html("<p>Es ist momentan kein Elternsprechtag als aktiv gesetzt!</p>");
    },
  });
}

function loadConnectedUsersForm() {
  $("#selectUser1").off("change");
  $("#selectUser2").off("change");
  $("#filterSameName").off("change");
  var connectedUsersForm = $("#connectedUsersForm");
  $.ajax({
    url: "viewController.php?action=getConnectedUsersForm",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      connectedUsersForm.html(data);
      $("#selectUser1").change(() => checkUserConnection(true));
      $("#selectUser2").change(() => checkUserConnection(false));
      $("#filterSameName").change(() => updateUser2Select());
    },
    error: function (jqXHR, textStatus, errorThrown) {
      connectedUsersForm.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}

function updateUser2Select() {
  const doFilter = $("#filterSameName").is(":checked");
  const selectedUserValue = +$("#selectUser1 option:selected").val();
  const selectedUserLastName =
    selectedUserValue !== -1 ? $("#selectUser1 option:selected").text().trim().split(" ")[0] : null;

  const allOptions = [];
  $("#selectUser1 option").each(function () {
    allOptions.push({ text: $(this).text(), value: +$(this).val() });
  });

  let secondUserOptions = [];
  if (doFilter && selectedUserLastName) {
    secondUserOptions = allOptions.filter(({ text, value }) => {
      return value === -1 || (text.trim().split(" ")[0] === selectedUserLastName && value !== selectedUserValue);
    });
  } else {
    secondUserOptions = allOptions;
  }

  $("#selectUser2").empty();

  secondUserOptions.forEach((option) => {
    $("#selectUser2").append($("<option>", { value: option.value, text: option.text }));
  });
}

function loadAllConnections() {
  var connectedUsersList = $("#allConnections");
  $.ajax({
    url: "viewController.php?action=getConnections",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      connectedUsersList.html(data);
      $(".editConnectionBtn").on("click", (e) => {
        const { userid1, userid2 } = e.target.dataset;
        $("#selectUser1").val(userid1);
        $("#selectUser2").val(userid2);
        checkUserConnection();
        document.getElementById("user-connection").scrollIntoView({ behavior: "smooth" });
      });
    },
    error: function (jqXHR, textStatus, errorThrown) {
      connectedUsersList.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}

function checkUserConnection(updateBoth) {
  const userId1 = $("#selectUser1").val();
  const userId2 = $("#selectUser2").val();
  const connectedUsersFeedback = $("#connectedUsersFeedback");

  if (userId1 != -1 && userId1 === userId2) {
    connectedUsersFeedback.html("<span class='text-danger'>Bitte zwei unterschiedliche Benutzer auswählen!</span>");
  } else if (userId1 != -1) {
    $.ajax({
      url: `viewController.php?action=checkUserConnection&userId1=${userId1}&userId2=${userId2}`,
      dataType: "html",
      type: "GET",
      success: function (data, textStatus, jqXHR) {
        connectedUsersFeedback.html(data);
        updateBoth && updateUser2Select();
        $("#userconnectionAction").click(() => toggleUserConnection(userId1, userId2));
      },
    });
  }
}

function toggleUserConnection(userId1, userId2, successFeedback) {
  const connectedUsersFeedback = $("#connectedUsersFeedback");
  $.ajax({
    url: "controller.php?action=toggleUserConnection",
    type: "POST",
    data: { userId1, userId2 },
    success: () => {
      if (successFeedback) {
        connectedUsersFeedback.html(successFeedback);
      }
      checkUserConnection();
      loadAllConnections();
    },
    error: () => {
      connectedUsersFeedback.html("<span class='text-danger'>Es ist ein Fehler aufgetreten.</span>");
    },
  });
}

$(document).on("change", "#selectTeacher", function (event) {
  displayActiveEvent();
});

$(document).on("click", "#btn-change-attendance", function () {
  $("#changeAttendanceForm").submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "changeAttendance" });

    var userId = postData[0].value;
    var eventId = postData[1].value;

    var formURL = "controller.php";
    $.ajax({
      url: formURL,
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        var message = $("#changeTimeMessage");
        if (data.indexOf("success") > -1) {
          $("#attendance").load(
            "viewController.php?action=attendanceParametrized&userId=" + userId + "&eventId=" + eventId
          );

          showMessage(message, "success", "Die Anwesenheit wurde erfolgreich geändert!");
          loadAllAttendances();
        } else {
          showMessage(message, "danger", "Die Anwesenheit konnte nicht geändert werden!");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showMessage(message, "danger", "Die Anwesenheit konnte nicht geändert werden!");
      },
    });
    e.preventDefault();
  });

  return true;
});

function loadAllAttendances() {
  const section = $("#all-attendances");
  $.ajax({
    url: "viewController.php?action=allAttendances",
    type: "GET",
    dataType: "html",
    success: (data) => {
      section.html(data);
      $(".selectableName").click((e) => {
        const teacherId = e.target.id.split("teacher_")[1];
        $("#selectTeacher").val(teacherId).change();
        document.getElementById("attendances").scrollIntoView({ behavior: "smooth" });
      });
    },
    error: () => {
      section.html("<span class='text-danger'>Es ist ein Fehler aufgetreten.</span>");
    },
  });
}

function saveEmailTemplate() {
  const templateId = $("#templateId").val();
  const subject = $("#emailTemplateSubject").val();
  const content = $("#emailTemplateContent").val().replace(/\n/g, "<br>");

  $.ajax({
    url: "controller.php",
    type: "POST",
    data: { action: "saveEmailTemplate", templateId, subject, content },
    success: function (data, textStatus, jqXHR) {
      showMessage($("#emailTemplateFeedback"), "success", "Die Vorlage wurde gespeichert.");
    },
  });
}

function addMailTemplateSelectListener() {
  const selectboxId = "#selectMailTemplate";
  const formElem = $("#templateForm");
  $(selectboxId).on("change", (e) => {
    const val = $(selectboxId).val();
    if (!val) {
      $("#emailTemplateFeedback").html("");
      formElem.html("");
    } else {
      $.ajax({
        url: "viewController.php?action=mailTemplateForm&templateId=" + val,
        type: "GET",
        dataType: "html",
        success: (data) => {
          formElem.html(data);
        },
        error: () => {
          formElem.html("<span class='text-danger'>Es ist ein Fehler aufgetreten.</span>");
        },
      });
    }
  });
}

function saveTextTemplate() {
  const templateId = $("#textTemplateId").val();
  const content = $("#textTemplateContent").val().replace(/\n/g, "<br>");

  $.ajax({
    url: "controller.php",
    type: "POST",
    data: { action: "saveTextTemplate", templateId, content },
    success: function (data, textStatus, jqXHR) {
      showMessage($("#textTemplateFeedback"), "success", "Der Hiweistext wurde gespeichert.");
    },
  });
}

function addTextTemplateSelectListener() {
  const selectboxId = "#selectArea";
  const formElem = $("#textTemplateForm");
  $(selectboxId).on("change", (e) => {
    const val = $(selectboxId).val();
    if (!val) {
      $("#textTemplateFeedback").html("");
      formElem.html("");
    } else {
      $.ajax({
        url: "viewController.php?action=textTemplateForm&templateId=" + val,
        type: "GET",
        dataType: "html",
        success: (data) => {
          formElem.html(data);
        },
        error: () => {
          formElem.html("<span class='text-danger'>Es ist ein Fehler aufgetreten.</span>");
        },
      });
    }
  });
}

function preparePasswordForm() {
  $("#sendAllPasswords")
    .off("click")
    .on("click", () => {
      const onConfirm = () =>
        $.ajax({
          url: "controller.php",
          type: "POST",
          data: { action: "sendAllPasswords" },
          success: function (data, textStatus, jqXHR) {
            showMessage($("#passwordFeedback"), "success", "Die E-Mails wurden versandt.");
          },
        });
      showConfirmationModal({
        title: "E-Mail-Versand bestätigen",
        content:
          "<h4 class='text-danger'>Sind Sie wirklich sicher?</h4><div><strong>ALLE</strong> Lehrer und SuS erhalten eine E-Mail.</div>",
        confirmationCaption: "Ja, Massenversand starten",
        onConfirm,
      });
    });

  $("#deletePasswords")
    .off("click")
    .on("click", () => {
      const onConfirm = () =>
        $.ajax({
          url: "controller.php",
          type: "POST",
          data: { action: "deleteAllPasswords" },
          success: function (data, textStatus, jqXHR) {
            showMessage($("#passwordFeedback"), "success", "Die Passwörter wurden gelöscht.");
          },
        });
      showConfirmationModal({
        title: "Löschen bestätigen",
        content:
          "<h5 class='text-danger'><strong>Die Passwörter aller Lehrkräfte und SchülerInnen werden gelöscht.</strong></h5>",
        confirmationCaption: "Ja, Passwörter löschen",
        onConfirm,
      });
    });
}
