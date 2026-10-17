<?php
include_once 'inc/header.php';
include_once 'code/dao/InvitationDAO.php';
include_once 'code/dao/EventDAO.php';
include_once 'code/dao/UserDAO.php';
require_once('code/AuthenticationManager.php');

$user = AuthenticationManager::getAuthenticatedUser();

if ($user->getRole() != "teacher") {
  http_response_code(403);
  exit("Unauthorized");
}

$invitations = InvitationDAO::getInvitationsFromTeacher($user->getId());
$event = EventDAO::getActiveEvent();

if ($event == null) {
  echo "Aktuell gibt es keinen Sprechtag";
  exit();
}

$invitationLimit = $event->getEarlyAccessLimit();
$isLimitReached = count($invitations) >= $invitationLimit;

?>

<script type='text/javascript' src='js/invitations.js'></script>

<div class='container invitations'>
  <h1>Einladungen</h1>
  <div>
    <?php if (count($invitations) == 0): ?>
      Du hast noch keine Einladungen verschickt.
    <?php else: ?>
      <table class="table invitation-table">
        <tr>
          <th>
            Einladung an
          </th>
          <th>
            Datum
          </th>
          <th></th>
        </tr>

        <?php foreach ($invitations as $i):
          $student = UserDAO::getUserForId($i->getStudentId());
        ?>
          <tr>
            <td>
              <?php echo $student->getLastName() . ", " . $student->getFirstName(); ?>
            </td>
            <td>
              <?php echo date('d.m.Y H:i', (int)$i->getCreatedAt())  . " Uhr"; ?>
            </td>
            <td>
              <button class="btn btn-danger withdraw-invitation"
                data-student-id="<?php echo (int)$i->getStudentId(); ?>"
                data-student-name="<?php echo htmlspecialchars($student->getFirstName() . ' ' . $student->getLastName()); ?>">Zurückziehen</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($isLimitReached): ?>
      <strong>Dein Kontingent für Einladungen ist ausgeschöpft.</strong>
    <?php else: ?>
      <div>
        <h4>Neue Einladung erstellen</h4>
        <form method="post" action="createInvitation.php">
          <input type="text" id="studentSearch" class="form-control" placeholder="Schüler suchen (mind. 3 Zeichen)" autocomplete="off">
          <input type="hidden" id="selectedStudentId" name="studentId" value="">
          <ul id="studentSearchResults" class="list-group" style="position: absolute; z-index: 1000; max-height: 300px; overflow-y: auto; cursor: pointer;"></ul>
          <br>
          <button type="submit" id="sendInvitation" class="btn btn-success" disabled>Einladung senden</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include_once 'inc/footer.php'; ?>