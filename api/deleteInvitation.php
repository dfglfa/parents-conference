<?php
require_once(__DIR__ . '/../code/AuthenticationManager.php');
require_once(__DIR__ . '/../code/dao/InvitationDAO.php');

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

// Only deletes invitations belonging to the authenticated teacher
if (InvitationDAO::deleteInvitationToParent($user->getId(), $studentId) != "OK") {
  http_response_code(500);
  exit("Error");
}

echo "OK";
