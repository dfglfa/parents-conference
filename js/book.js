$(document).ready(function () {
  $("#selectTeacher").change(function () {
    var teacherSelect = $("#selectTeacher");
    teacherSelect.find("option[value='-1']").remove();

    teacherSelect = teacherSelect.find("option:selected");
    var teacherId = teacherSelect.val();

    loadTimeTable(teacherId);
  });
});

$(document).on("click", ".btn-book", function (event) {
  var postData = $.parseJSON(this.value);
  var teacherId = postData.teacherId;
  var errorText = "<h3>Beim Laden der Termine ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>";
  postData.action = "changeSlot";

  const onConfirm = () =>
    $.ajax({
      url: "controller.php",
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          loadTimeTable(teacherId);
        } else if (data.indexOf("dirtyRead") > -1) {
          loadTimeTable(teacherId);
          alert(
            "WARNUNG!\n\nDer gewünschte Termin wurde in der Zwischenzeit vergeben! Bitte wählen Sie einen anderen Termin!"
          );
        } else {
          $("#timeTable").html(errorText);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#timeTable").html(errorText);
      },
    });

  showConfirmationModal({
    title: "Buchung bestätigen",
    content: `<strong>Soll der Termin verbindlich gebucht werden?</strong>
    <br><br>
    Die Lehrkraft wird per E-Mail informiert.`,
    confirmationCaption: "Ja, Termin buchen",
    onConfirm,
  });
});

function loadQuotaInfo() {
  const quotaElem = $("#quotaInformation");
  $.ajax({
    url: "viewController.php?action=getMyQuota",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      quotaElem.html(data);
    },
  });
}

function loadTimeTable(teacherId) {
  var timeTable = $("#timeTable");
  $.ajax({
    url: "viewController.php?action=getTimeTable&teacherId=" + teacherId,
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      timeTable.html(data);
      loadQuotaInfo();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      timeTable.html("<h1>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h1>");
    },
  });
}
