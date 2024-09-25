<?php
require_once('config.php');
require_once('Util.php');

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

    $trans = array(
        '{SLOT_TIME}' => getSlotTimeReadable($slotData["dateFrom"]),
        '{STUDENT_NAME}' => $slotData["studentName"],
        '{TEACHER_NAME}' => $slotData["teacherName"],
    );

    // Mail to student
    $studentMailData = getDataForMailTemplate("bookSlotMailToStudent");
    $emailContentStudent = $studentMailData["content"];
    $emailContentStudent = strtr($emailContentStudent, $trans);
    $emailSubjectStudent = $studentMailData["subject"];
    $emailSubjectStudent = strtr($emailSubjectStudent, $trans);

    $studentEmail = $slotData["studentEmail"];

    if (!empty($studentEmail)) {
        sendMail($studentEmail, $emailSubjectStudent, $emailContentStudent);
    }

    // Mail to teacher
    $teacherMailData = getDataForMailTemplate("bookSlotMailToTeacher");
    $emailContentTeacher = $teacherMailData["content"];
    $emailContentTeacher = strtr($emailContentTeacher, $trans);
    $emailSubjectTeacher = $teacherMailData["subject"];
    $emailSubjectTeacher = strtr($emailSubjectTeacher, $trans);

    $teacherEmail = $slotData["teacherEmail"];
    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, $emailSubjectTeacher, $emailContentTeacher);
    }
}

function sendCancellationNotificationMail($slotId, $reasonText)
{
    $slotData = SlotDAO::getNamesAndEmailAddressesForSlotId($slotId);

    if ($slotData == null) {
        error_log("No slot found for ID " . $slotId);
        return;
    }

    $trans = array(
        '{SLOT_TIME}' => getSlotTimeReadable($slotData["dateFrom"]),
        '{STUDENT_NAME}' => $slotData["studentName"],
        '{TEACHER_NAME}' => $slotData["teacherName"],
        '{CANCELLATION_MESSAGE}' => $reasonText,
    );

    // Mail to student
    $studentMailData = getDataForMailTemplate("slotCancelledByTeacherMailToStudent");
    $emailContentStudent = $studentMailData["content"];
    $emailContentStudent = strtr($emailContentStudent, $trans);
    $emailSubjectStudent = $studentMailData["subject"];
    $emailSubjectStudent = strtr($emailSubjectStudent, $trans);

    $studentEmail = $slotData["studentEmail"];

    if (!empty($studentEmail)) {
        sendMail($studentEmail, $emailSubjectStudent, $emailContentStudent);
    }

    // Mail to teacher
    $teacherMailData = getDataForMailTemplate("slotCancelledByStudentMailToTeacher");
    $emailContentTeacher = $teacherMailData["content"];
    $emailContentTeacher = strtr($emailContentTeacher, $trans);
    $emailSubjectTeacher = $teacherMailData["subject"];
    $emailSubjectTeacher = strtr($emailSubjectTeacher, $trans);

    $teacherEmail = $slotData["teacherEmail"];
    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, $emailSubjectTeacher, $emailContentTeacher);
    }
}

function getSlotTimeReadable($date)
{
    return toDate($date, "H:i") . " Uhr";
}

function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);
    global $SMTP_FROM, $SMTP_HOST, $SMTP_PORT, $SMTP_AUTH, $SMTP_LOGIN, $SMTP_PWD;

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->Username = $SMTP_LOGIN;
        $mail->Password = $SMTP_PWD;
        $mail->Port = $SMTP_PORT;
        $mail->CharSet = "UTF-8";

        if ($SMTP_AUTH) {
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        //Recipients
        $mail->setFrom($SMTP_FROM);
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
