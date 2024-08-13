$(document).ready(loadSiblings);

$(document).on("click", ".linkStudentBtn", function (e) {
  const elemId = e.target.id;
  const studentId = $("#" + elemId).attr("data-studentid");
  const postData = { action: "linkStudent", studentId };

  $.ajax({
    url: "controller.php",
    type: "POST",
    data: postData,
    success: function () {
      window.location.reload();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $("#timeTable").html("<h3>Es ist ein Fehler aufgetreten</h3>");
    },
  });
});

function loadSiblings() {
  var siblingsForm = $("#siblingsList");
  $.ajax({
    url: "viewController.php?action=getSiblingsForm",
    dataType: "html",
    type: "GET",
    success: function (data, textStatus, jqXHR) {
      siblingsForm.html(data);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      siblingsForm.html("<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>");
    },
  });
}
