var MIN_SEARCH_LENGTH = 3;
var SEARCH_DEBOUNCE_MS = 250;

$(document).ready(function () {
  var searchInput = $("#studentSearch");
  var results = $("#studentSearchResults");
  var selectedIdInput = $("#selectedStudentId");
  var searchTimeout = null;
  var currentRequest = null;
  var activeIndex = -1;

  searchInput.on("input", function () {
    var term = searchInput.val().trim();
    clearTimeout(searchTimeout);
    abortRequest();
    clearSelection();

    if (term.length < MIN_SEARCH_LENGTH) {
      closeResults();
      return;
    }

    searchTimeout = setTimeout(function () {
      searchStudents(term);
    }, SEARCH_DEBOUNCE_MS);
  });

  searchInput.on("keydown", function (e) {
    var items = results.find(".student-result");

    switch (e.key) {
      case "ArrowDown":
        if (items.length) {
          e.preventDefault();
          setActive((activeIndex + 1) % items.length);
        }
        break;
      case "ArrowUp":
        if (items.length) {
          e.preventDefault();
          setActive(activeIndex <= 0 ? items.length - 1 : activeIndex - 1);
        }
        break;
      case "Enter":
        e.preventDefault();
        if (activeIndex >= 0) {
          selectStudent(items.eq(activeIndex));
        }
        break;
      case "Escape":
        closeResults();
        break;
    }
  });

  // mousedown instead of click, so the selection happens before the input loses focus
  results.on("mousedown", ".student-result", function (e) {
    e.preventDefault();
    selectStudent($(this));
  });

  results.on("mouseenter", ".student-result", function () {
    setActive($(this).index());
  });

  searchInput.on("blur", closeResults);

  function searchStudents(term) {
    currentRequest = $.ajax({
      url: "api/fetchStudents.php",
      type: "GET",
      dataType: "json",
      data: { q: term },
      success: function (students) {
        results.empty();
        activeIndex = -1;
        if (students.length === 0) {
          results.append($("<li>").addClass("list-group-item disabled").text("Keine Treffer"));
          return;
        }
        students.forEach(function (s) {
          results.append(
            $("<li>")
              .addClass("list-group-item student-result")
              .attr("data-student-id", s.id)
              .data("label", s.lastName + ", " + s.firstName + " (" + s.class + ")")
              .text(s.lastName + ", " + s.firstName + " (" + s.class + ")")
          );
        });
        setActive(0);
      },
      error: function (jqXHR, textStatus) {
        if (textStatus === "abort") {
          return;
        }
        results.empty().append($("<li>").addClass("list-group-item text-danger").text("Es ist ein Fehler aufgetreten."));
      },
    });
  }

  function setActive(index) {
    var items = results.find(".student-result");
    items.removeClass("active");
    activeIndex = index;
    if (index >= 0 && index < items.length) {
      var item = items.eq(index).addClass("active");
      var el = item[0];
      if (el.scrollIntoView) {
        el.scrollIntoView({ block: "nearest" });
      }
    }
  }

  function selectStudent(item) {
    selectedIdInput.val(item.attr("data-student-id"));
    updateSendButton();
    searchInput.val(item.data("label"));
    closeResults();
  }

  function clearSelection() {
    selectedIdInput.val("");
    updateSendButton();
  }

  function updateSendButton() {
    $("#sendInvitation").prop("disabled", selectedIdInput.val() === "");
  }

  function closeResults() {
    clearTimeout(searchTimeout);
    abortRequest();
    results.empty();
    activeIndex = -1;
  }

  function abortRequest() {
    if (currentRequest) {
      currentRequest.abort();
      currentRequest = null;
    }
  }
});

$(document).on("click", ".withdraw-invitation", function () {
  var studentId = $(this).data("student-id");
  var studentName = $("<div>").text($(this).data("student-name")).html();

  showConfirmationModal({
    title: "Einladung zurückziehen",
    content: "Möchtest Du die Einladung an <strong>" + studentName + "</strong> wirklich zurückziehen?",
    confirmationCaption: "Ja, zurückziehen",
    onConfirm: function () {
      $.post("api/deleteInvitation.php", { studentId: studentId })
        .done(function () {
          location.reload();
        })
        .fail(function () {
          alert("Die Einladung konnte nicht zurückgezogen werden.");
        });
    },
  });
});
