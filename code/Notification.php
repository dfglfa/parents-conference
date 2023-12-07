<?php
require_once('Util.php');
require_once('email.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'inc/PHPMailer/Exception.php';
require 'inc/PHPMailer/PHPMailer.php';
require 'inc/PHPMailer/SMTP.php';


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
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>" .
        "<hr />" .
        "<div> " .
        "<p>Bonjour " . $studentName . ", </p> " .
        "<p>Le rendez-vous avec " . $teacherName . " le " . toDate($date, "d.m.Y") .
        " à " . toDate($date, "H:i") . " a bien été réservé.</p>" .
        "</div>" .
        "<div>Cordialement, <br> Les admins</div>";

    $emailTemplateTeacher = "<div> " .
        "<p>Guten Tag " . $teacherName . ", </p> " .
        "<p>Soeben wurde von " . $studentName . " ein Termin am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr gebucht.</p>" .
        "<div>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>" .
        "<hr />" .
        "<div> " .
        "<p>Bonjour " . $teacherName . ", </p> " .
        "<p>" . $studentName . " a réservé un rendez-vous le " . toDate($date, "d.m.Y") .
        " à " . toDate($date, "H:i") . ".</p>" .
        "<div>" .
        "<div>Cordialement, <br> Les admins</div>";

    if (!empty($studentEmail)) {
        sendMail($studentEmail, "Terminbestätigung / Confirmation de rendez-vous : " . $teacherName . " - " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateStudent);
    }

    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, "Neue Terminbuchung von " . $studentName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateTeacher);
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
        "<p>" . $teacherName . " möchte den geplanten Termin am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr verschieben.</p>" .
        "</div>" .
        "<div>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>" .
        "<hr />" .
        "<div> " .
        "<p>Bonjour " . $studentName . ", </p> " .
        "<p>" . $teacherName . " préférerait déplacer le rendez-vous prévu le " . toDate($date, "d.m.Y") .
        " à " . toDate($date, "H:i") . ".</p>" .
        "</div>" .
        "<div>" .
        "<div>Cordialement, <br> Les admins</div>";

    $emailTemplateTeacher = "<div> " .
        "<p>Guten Tag " . $teacherName . ", </p> " .
        "<p>Ihr Termin mit " . $studentName . " am " . toDate($date, "d.m.Y") .
        " um " . toDate($date, "H:i") . " Uhr wurde verschoben.</p>" .
        "<div>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>";


    if (!empty($studentEmail)) {
        sendMail($studentEmail, "Terminverschiebung / Déplacement de rendez-vous : " . $teacherName . " - " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateStudent);
    }

    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, "Terminverschiebung von " . $studentName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateTeacher);
    }
}

function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = SMTPConfig::$SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTPConfig::$SMTP_LOGIN;
        $mail->Password = SMTPConfig::$SMTP_PWD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = "UTF-8";

        //Recipients
        $mail->setFrom(SMTPConfig::$SMTP_FROM);
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

}
