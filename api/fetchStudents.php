<?php
require_once(__DIR__ . '/../code/AuthenticationManager.php');
require_once(__DIR__ . '/../code/dao/UserDAO.php');
require_once(__DIR__ . '/../code/dao/InvitationDAO.php');

header('Content-Type: application/json; charset=utf-8');

$user = AuthenticationManager::getAuthenticatedUser();

if ($user == null || !in_array($user->getRole(), array("teacher", "admin"))) {
  http_response_code(403);
  echo json_encode(array('error' => 'Unauthorized'));
  exit;
}

$term = trim($_GET['q'] ?? '');

if (mb_strlen($term) < 3) {
  http_response_code(400);
  echo json_encode(array('error' => 'Mindestens 3 Zeichen erforderlich'));
  exit;
}

// Teachers should not be offered students they have already invited
$invitedIds = array();
if ($user->getRole() == "teacher") {
  foreach (InvitationDAO::getInvitationsFromTeacher($user->getId()) as $invitation) {
    $invitedIds[] = (string)$invitation->getStudentId();
  }
}

$candidates = array_values(array_filter(UserDAO::searchStudents($term), function ($s) use ($invitedIds) {
  return !in_array((string)$s->getId(), $invitedIds, true);
}));

$students = array_map(function ($s) {
  return array(
    'id' => $s->getId(),
    'firstName' => $s->getFirstName(),
    'lastName' => $s->getLastName(),
    'class' => $s->getClass(),
  );
}, $candidates);

echo json_encode($students);
