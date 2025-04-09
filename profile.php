<?php include_once 'inc/header.php'; ?>

<script type="text/javascript" src="js/password.js"></script>

<?php
$user = AuthenticationManager::getAuthenticatedUser();

function getRoleInGerman($role)
{
  switch ($role) {
    case 'admin':
      return 'Administrator';
      break;
    case 'student':
      return 'Schüler';
      break;
    case 'teacher':
      return 'Lehrer';
      break;
    default:
      return 'Unbekannt';
  }
}
?>

<div class='container'>
  <h1>Benutzerprofil</h1>
  <table class='table table-striped'>
    <tr>
      <th>Benutzername</th>
      <td><?php echo escape($user->getUserName()); ?></td>
    </tr>
    <tr>
      <th>Vorname</th>
      <td><?php echo escape($user->getFirstName()); ?></td>
    </tr>
    <tr>
      <th>Nachname</th>
      <td><?php echo escape($user->getLastName()); ?></td>
    </tr>
    <tr>
      <th>Klasse</th>
      <td><?php echo escape($user->getClass()); ?></td>
    </tr>
    <tr>
      <th>Rolle</th>
      <td><?php echo escape(getRoleInGerman($user->getRole())); ?></td>
    </tr>
  </table>

  <?php if ($user->getRole() == "student"): ?>
    <script type='text/javascript' src='js/siblings.js'></script>

    <div>
      <h1>Geschwister</h1>

      <div id='siblingsList'></div>
    </div>
  <?php endif; ?>

  <div style="padding-top: 20px">
    <h2>Passwort ändern</h2>
    <div class='form-group'>
      <label for='passwordOld'>Aktuelles Passwort:</label>
      <div class='input-group' id='passwordOldLine'>
        <input type='password' class='form-control' id='passwordOld' name='passwordOld'
          placeholder='Bitte aktuelles Passwort eingeben' size="30">
      </div>
    </div>

    <div class='form-group'>
      <label for='passwordNew'>Neues Passwort:</label>
      <div class='input-group' id='passwordNewLine'>
        <input type='password' class='form-control' id='passwordNew' name='passwordNew' size="30">
      </div>
    </div>

    <div class='form-group'>
      <label for='passwordNew2'>Passwort wiederholen:</label>
      <div class='input-group' id='passwordNew2Line'>
        <input type='password' class='form-control' id='passwordNew2' name='passwordNew2' size="30">
      </div>
    </div>


    <div>
      <button onclick="submitPassword()" class="btn btn-primary" id="btn-submit-password">Ändern</button>
    </div>

    <br>
    <div id="passwordFeedback" class="text-danger"></div>

  </div>
</div>

<?php include_once 'inc/footer.php'; ?>