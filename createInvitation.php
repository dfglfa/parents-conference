<?php
require_once('code/AuthenticationManager.php');
require_once('code/dao/InvitationDAO.php');
require_once('code/dao/EventDAO.php');
require_once('code/dao/UserDAO.php');

$user = AuthenticationManager::getAuthenticatedUser();

if ($user == null || $user->getRole() != "teacher" || $_SERVER['REQUEST_METHOD'] != 'POST') {
  http_response_code(403);
  exit("Unauthorized");
}

$event = EventDAO::getActiveEvent();
$studentId = $_POST['studentId'] ?? '';
$student = ctype_digit($studentId) ? UserDAO::getUserForId($studentId) : null;

if ($event == null || $student == null || $student->getRole() != "student") {
  http_response_code(400);
  exit("Bad request");
}

$invitations = InvitationDAO::getInvitationsFromTeacher($user->getId());
if (count($invitations) >= $event->getEarlyAccessLimit()) {
  http_response_code(409);
  exit("Invitation limit reached");
}

try {
  InvitationDAO::createInvitationToStudent($user->getId(), $studentId);
} catch (PDOException $e) {
  // Most likely a duplicate (unique key teacher/student): the invitation already exists
  error_log("Creating invitation failed: " . $e->getMessage());
}

header('Location: invitations.php');
exit;
