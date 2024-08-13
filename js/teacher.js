$(document).ready(function () {
  loadTimeTable(2);

  $("#selectType").change(function () {
    var typeSelect = $("#selectType").find("option:selected");
    var typeId = typeSelect.val();

    loadTimeTable(typeId);
  });
});

$(document).on("click", "#btn-change-attendance", function () {
  $("#changeAttendanceForm").submit(function (e) {
    var postData = $(this).serializeArray();
    postData = postData.concat({ name: "action", value: "changeAttendance" });

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

function addButtonInteractivity() {
  $(".es-button-cancel").on("click", (e) => {
    const buttonIdHash = "#" + e.target.id;
    $(buttonIdHash).prop("disabled", "disabled");

    // Fetch all required data from HTML element
    const slotId = $(buttonIdHash).attr("data-slotId");
    const teacherId = $(buttonIdHash).attr("data-teacherId");
    const studentId = $(buttonIdHash).attr("data-studentId");
    const eventId = $(buttonIdHash).attr("data-eventId");

    // Create IDs for a new DOM node.
    const rowId = "row_" + slotId;
    const reasonId = "reason_" + slotId;
    const submitButtonId = "submit_" + slotId;
    const cancelButtonId = "cancel_" + slotId;

    const reasonHtml = `<div class='input-group'>
                          <input class='form-control' 
                            type='text' 
                            placeholder='Bitte gib einen kurzen Begründungstext ein' 
                            id='${reasonId}'>
                          <div class="input-group-btn">
                            <button class="btn btn-danger btn-no-margin" 
                              type="button"
                              id='${cancelButtonId}'>
                              Abbrechen
                            </button><button class='btn btn-success btn-no-margin' 
                              type='button' 
                              id='${submitButtonId}'>
                              Speichern
                            </button>
                          </div>
                        </div>`;

    const reasonRowId = "reasonRow_" + slotId;
    $("#" + rowId).after(`<tr id="${reasonRowId}"><td colspan="3">${reasonHtml}</td></tr>`);
    $("#" + reasonId).focus();

    $("#" + cancelButtonId).on("click", () => {
      $("#" + reasonRowId).fadeOut(400, () => $("#" + reasonRowId).remove);
      $(buttonIdHash).prop("disabled", null);
    });

    $("#" + submitButtonId).attr("disabled", true);
    $("#" + reasonId).on("keyup", (e) => {
      if (e.target.value?.trim()) {
        $("#" + submitButtonId).attr("disabled", false);
      } else {
        $("#" + submitButtonId).attr("disabled", true);
      }
    });

    $("#" + submitButtonId).on("click", () => {
      const reasonText = $("#" + reasonId).val();
      const postData = { action: "createCancellationMessage", slotId, eventId, studentId, teacherId, reasonText };

      $.ajax({
        url: "controller.php",
        type: "POST",
        data: postData,
        success: function (data, textStatus, jqXHR) {
          $("#" + reasonRowId).html(data);
          executeDelete(teacherId, slotId, eventId, rowId, reasonText);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          $("#timeTable").html(errorText);
        },
      });
    });
  });
}

function executeDelete(userId, slotId, eventId, rowId, reasonText) {
  const postData = { action: "deleteSlot", slotId, eventId, userId, reasonText };
  $.ajax({
    url: "controller.php",
    type: "POST",
    data: postData,
    success: function (data, textStatus, jqXHR) {
      $("#" + rowId).fadeOut(400, $("#" + rowId).remove);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error(textStatus);
    },
  });
}

function loadTimeTable(typeId) {
  var timeTable = $("#timeTable");
  $.ajax({
    url: "viewController.php?action=getTeacherTimeTable&typeId=" + typeId,
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      timeTable.html(data);
      addButtonInteractivity();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      timeTable.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}
