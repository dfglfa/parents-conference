$(document).ready(function () {
  loadTimeTable(0);

  $("#showempty").change(() => {
    const val = $("#showempty").is(":checked");
    loadTimeTable(val ? 1 : 0);
  });

  $(".messageDismissal").on("click", (e) => {
    const elemId = e.target.id;
    const msgId = $("#" + elemId).attr("data-messageid");
    const receiverId = $("#" + elemId).attr("data-receiverid");
    dismissMessage(msgId, receiverId);
  });
});

$(document).on("click", ".btn-delete", function (event) {
  const postData = $.parseJSON(this.value);
  const typeId = postData.typeId;
  const errorText = "<h3>Beim Stornieren ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>";
  postData.action = "deleteSlot";

  const onDelete = () =>
    $.ajax({
      url: "controller.php",
      type: "POST",
      data: postData,
      success: function (data, textStatus, jqXHR) {
        if (data.indexOf("success") > -1) {
          loadTimeTable(typeId);
        } else {
          $("#timeTable").html(errorText);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $("#timeTable").html(errorText);
      },
    });

  showConfirmationModal({
    title: "Stornierung bestätigen",
    content:
      "<strong>Soll der Termin wirklich storniert werden?</strong><br><br>Die Lehrkraft wird per E-Mail informiert.",
    confirmationCaption: "Ja, stornieren",
    onConfirm: onDelete,
  });
});

function loadTimeTable(typeId) {
  var timeTable = $("#timeTable");
  $.ajax({
    url: "viewController.php?action=getMySlotsTable&typeId=" + typeId,
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

function dismissMessage(messageId, receiverId) {
  $.ajax({
    url: "controller.php",
    data: { action: "dismissMessage", messageId, receiverId },
    type: "POST",
    success: function (data, textStatus, jqXHR) {
      console.log("Dismissed");
    },
    error: function (jqXHR, textStatus, errorThrown) {
      timeTable.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}
