$(document).ready(loadSiblings);

function loadSiblings() {
  var siblingsForm = $("#siblingsList");
  $.ajax({
    url: "viewController.php?action=getSiblingsList",
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
