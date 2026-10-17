<?php
require_once(__DIR__ . '/../code/AuthenticationManager.php');
require_once(__DIR__ . '/../code/dao/InvitationDAO.php');
require_once(__DIR__ . '/../code/dao/EventDAO.php');
require_once(__DIR__ . '/../code/dao/SlotDAO.php');
require_once(__DIR__ . '/../code/Notification.php');

$user = AuthenticationManager::getAuthenticatedUser();

if ($user == null || $user->getRole() != "teacher" || $_SERVER['REQUEST_METHOD'] != 'POST') {
  http_response_code(403);
  exit("Unauthorized");
}

$studentId = $_POST['studentId'] ?? '';
if (!ctype_digit($studentId)) {
  http_response_code(400);
  exit("Bad request");
}

// An invitation that led to a booked slot cannot be withdrawn (until the student cancels)
$event = EventDAO::getActiveEvent();
if ($event != null && SlotDAO::hasBookedSlot($event->getId(), $user->getId(), $studentId)) {
  http_response_code(409);
  exit("Invitation has a booked slot");
}

// Only deletes invitations belonging to the authenticated teacher
$existed = InvitationDAO::hasInvitation($user->getId(), $studentId);
if (InvitationDAO::deleteInvitationToParent($user->getId(), $studentId) != "OK") {
  http_response_code(500);
  exit("Error");
}

if ($existed) {
  sendInvitationNotificationMails($user->getId(), $studentId, true);
}

echo "OK";
