<?php
require_once('Util.php');
require_once('config.php');

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
        error_log("No slot found for ID " . $slotId);
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

function sendCancellationNotificationMail($slotId, $reasonText)
{
    $slotData = SlotDAO::getNamesAndEmailAddressesForSlotId($slotId);

    if ($slotData == null) {
        error_log("No slot found for ID " . $slotId);
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
        ($reasonText != null ? "<div>Kommentar der Lehrkraft: <br/><strong>" . $reasonText . "</strong></div><br/>" : "") .
        "<div>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>" .
        "<hr />" .
        "<div> " .
        "<p>Bonjour " . $studentName . ", </p> " .
        "<p>" . $teacherName . " préférerait déplacer le rendez-vous prévu le " . toDate($date, "d.m.Y") .
        " à " . toDate($date, "H:i") . ".</p>" .
        "</div>" .
        ($reasonText != null ? "<div>Commentaire de l'enseignant(e): <br/><strong>" . $reasonText . "</strong></div><br/>" : "") .
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
    } else {
        error_log("No email address found for student");
    }

    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, "Terminverschiebung von " . $studentName . " am " . toDate($date, "d.m.Y H:i") . " Uhr", $emailTemplateTeacher);
    } else {
        error_log("No email address found for teacher");
    }
}

function sendUserConnectionInfo($userId1, $userId2)
{
    $user1 = UserDAO::getUserForId($userId1);
    $user2 = UserDAO::getUserForId($userId2);

    if ($user1 == null || $user2 == null) {
        error_log("Not all users found for IDs " . $userId1 . " and " . $userId2);
        return;
    }

    $emailTemplate = "<div> " .
        "<p>Guten Tag " . $user2->getFirstName() . " " . $user2->getLastName() . ", </p> " .
        "<p>" . $user1->getFirstName() . " " . $user1->getLastName() . " hat Dich beim Elternsprechtag als Bruder/Schwester angegeben.<p>" .
        "<p>Eure Benutzer sind ab jetzt verknüpft. Falls das nicht korrekt ist, melde Dich bitte bei <a href='mailto:" . Config::$SMTP_FROM . "'>" . Config::$SMTP_FROM . "</a>" .
        "<div>Viele Grüße, <br> Die Elternsprechtag-Admins</div>" .
        "<br/>---------------------------------<br/>" .
        "<p>Bonjour " . $user2->getFirstName() . " " . $user2->getLastName() . ", </p> " .
        "<p>" . $user1->getFirstName() . " " . $user1->getLastName() . "  t'a déclaré comme frère/sœur lors de la réunion parents-professeurs.<p>" .
        "<p>Vos comptes utilisateurs sont maintenant liés. Si cela n'est pas correct, merci de contacter <a href='mailto:" . Config::$SMTP_FROM . "'>" . Config::$SMTP_FROM . "</a>" .
        "<div>Cordialement, <br> Les admins</div>";

    sendMail($user2->getEmail(), "Elternsprechtag: Konten verknüpft / Liens des comptes", $emailTemplate);
}


function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = Config::$SMTP_HOST;
        $mail->Username = Config::$SMTP_LOGIN;
        $mail->Password = Config::$SMTP_PWD;
        $mail->Port = Config::$SMTP_PORT;
        $mail->CharSet = "UTF-8";

        if (Config::$SMTP_AUTH) {
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        //Recipients
        $mail->setFrom(Config::$SMTP_FROM);
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        error_log('Message has been sent');
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }

}
