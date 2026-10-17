<?php
require_once('config.php');
require_once('Util.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../inc/PHPMailer/Exception.php';
require __DIR__ . '/../inc/PHPMailer/PHPMailer.php';
require __DIR__ . '/../inc/PHPMailer/SMTP.php';


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
    $studentMailData = getDataForJsonTemplate("bookSlotMailToStudent");
    $emailContentStudent = $studentMailData["content"];
    $emailContentStudent = strtr($emailContentStudent, $trans);
    $emailSubjectStudent = $studentMailData["subject"];
    $emailSubjectStudent = strtr($emailSubjectStudent, $trans);

    $studentEmail = $slotData["studentEmail"];

    if (!empty($studentEmail)) {
        sendMail($studentEmail, $emailSubjectStudent, $emailContentStudent);
    }

    // Mail to teacher
    $teacherMailData = getDataForJsonTemplate("bookSlotMailToTeacher");
    $emailContentTeacher = $teacherMailData["content"];
    $emailContentTeacher = strtr($emailContentTeacher, $trans);
    $emailSubjectTeacher = $teacherMailData["subject"];
    $emailSubjectTeacher = strtr($emailSubjectTeacher, $trans);

    $teacherEmail = $slotData["teacherEmail"];
    if (!empty($teacherEmail)) {
        sendMail($teacherEmail, $emailSubjectTeacher, $emailContentTeacher);
    }
}

function sendCancellationNotificationMailToStudent($slotId, $reasonText)
{
    _sendCancellationNotificationMail($slotId, $reasonText, true);
}

function sendCancellationNotificationMailToTeacher($slotId)
{
    _sendCancellationNotificationMail($slotId, "", false);
}

function _sendCancellationNotificationMail($slotId, $reasonText, $recipientIsStudent)
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

    if ($recipientIsStudent) {
        $studentMailData = getDataForJsonTemplate("slotCancelledByTeacherMailToStudent");
        $emailContentStudent = $studentMailData["content"];
        $emailContentStudent = strtr($emailContentStudent, $trans);
        $emailSubjectStudent = $studentMailData["subject"];
        $emailSubjectStudent = strtr($emailSubjectStudent, $trans);

        $studentEmail = $slotData["studentEmail"];

        if (!empty($studentEmail)) {
            sendMail($studentEmail, $emailSubjectStudent, $emailContentStudent);
        }
    } else {
        // Mail to teacher
        $teacherMailData = getDataForJsonTemplate("slotCancelledByStudentMailToTeacher");
        $emailContentTeacher = $teacherMailData["content"];
        $emailContentTeacher = strtr($emailContentTeacher, $trans);
        $emailSubjectTeacher = $teacherMailData["subject"];
        $emailSubjectTeacher = strtr($emailSubjectTeacher, $trans);

        $teacherEmail = $slotData["teacherEmail"];
        if (!empty($teacherEmail)) {
            sendMail($teacherEmail, $emailSubjectTeacher, $emailContentTeacher);
        }
    }
}

function sendInvitationNotificationMails($teacherId, $studentId, $withdrawn)
{
    $teacher = UserDAO::getUserForId($teacherId);
    $student = UserDAO::getUserForId($studentId);

    if ($teacher == null || $student == null) {
        error_log("Invitation mail: user not found (teacher " . $teacherId . ", student " . $studentId . ")");
        return;
    }

    $trans = array(
        '{STUDENT_NAME}' => trim($student->getTitle() . ' ' . $student->getFirstName() . ' ' . $student->getLastName()),
        '{TEACHER_NAME}' => trim($teacher->getTitle() . ' ' . $teacher->getFirstName() . ' ' . $teacher->getLastName()),
    );

    $email = $student->getEmail();
    if (empty($email)) {
        return;
    }

    $mailData = getDataForJsonTemplate($withdrawn ? "invitationWithdrawnMailToStudent" : "invitationCreatedMailToStudent");
    if ($mailData == null) {
        return;
    }
    sendMail($email, strtr($mailData["subject"], $trans), strtr($mailData["content"], $trans));
}

function getSlotTimeReadable($date)
{
    return toDate($date, "H:i");
}

function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);
    global $SMTP_FROM, $SMTP_HOST, $SMTP_PORT, $SMTP_AUTH, $SMTP_LOGIN, $SMTP_PWD;

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';
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
