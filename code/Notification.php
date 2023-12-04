<?php
require_once('Util.php');

function sendCreationNotificationMail($slotId)
{
    $slotData = SlotDAO::getNamesAndEmailAddressesForSlotId($slotId);

    if ($slotData == null) {
        echo ("No slot found for ID " . $slotId);
        return;
    }

    $studentName = $slotData["studentName"];
    $studentEmail = $slotData["studentEmail"];

    $teacherName = $slotData["teacherName"];
    $teacherEmail = $slotData["teacherEmail"];

    $date = $slotData["dateFrom"];

    $emailTemplateStudent = "<div> " .
        "<p>Guten Tag " . $studentName . ", </p> " .
        "<p>Es wurde ein Termin mit " . $teacherName . " am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr vereinbart.</p>" .
        "</div>" .
        "<br>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>";

    $emailTemplateTeacher = "<div> " .
        "<p>Guten Tag " . $teacherName . ", </p> " .
        "<p>Soeben wurde von " . $studentName . " ein Termin am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr gebucht.</p>" .
        "<div>" .
        "<br>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>";

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';

    if (!empty($studentEmail)) {
        mail($studentEmail, "Terminbestätigung " . $teacherName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateStudent, implode("\r\n", $headers));
    }

    if (!empty($teacherEmail)) {
        mail($teacherEmail, "Neue Terminbuchung von " . $studentName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateTeacher, implode("\r\n", $headers));
    }
}

function sendCancellationNotificationMail($slotId)
{
    $slotData = SlotDAO::getNamesAndEmailAddressesForSlotId($slotId);

    if ($slotData == null) {
        echo ("No slot found for ID " . $slotId);
        return;
    }

    $studentName = $slotData["studentName"];
    $studentEmail = $slotData["studentEmail"];

    $teacherName = $slotData["teacherName"];
    $teacherEmail = $slotData["teacherEmail"];

    $date = $slotData["dateFrom"];

    $emailTemplateStudent = "<div> " .
        "<p>Guten Tag " . $studentName . ", </p> " .
        "<p>Der Termin mit " . $teacherName . " am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr wurde abgesagt.</p>" .
        "</div>" .
        "<div>" .
        "<br>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>";

    $emailTemplateTeacher = "<div> " .
        "<p>Guten Tag " . $teacherName . ", </p> " .
        "<p>Ihr Termin mit " . $studentName . " am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr wurde abgesagt.</p>" .
        "<div>" .
        "<br>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>";

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';

    if (!empty($studentEmail)) {
        mail($studentEmail, "Terminabsage " . $teacherName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateStudent, implode("\r\n", $headers));
    }

    if (!empty($teacherEmail)) {
        mail($teacherEmail, "Terminabsage von " . $studentName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateTeacher, implode("\r\n", $headers));
    }
}