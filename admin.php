<?php
require_once('code/AuthenticationManager.php');
require_once('code/ViewController.php');
AuthenticationManager::checkPrivilege('admin');

include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/admin.js'></script>
<script type='text/javascript' src='js/validation.min.js'></script>

<script src='libs/bootstrap/js/bootstrap-datepicker.min.js'></script>
<script src='libs/bootstrap/locales/bootstrap-datepicker.de.min.js'></script>
<script type="text/javascript" src="libs/bootstrap/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="libs/bootstrap/js/bootstrap-datetimepicker.de.js" charset="UTF-8"></script>
<link href='libs/bootstrap/css/bootstrap-datepicker3.min.css' rel='stylesheet'>
<link href='libs/bootstrap/css/bootstrap-datetimepicker.css' rel='stylesheet'>

<p id='pageName' hidden>Admin</p>

<div class='container'>

  <h1>Administration</h1>

  <div class='panel-group' id='accordion'>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#upload'>
            Daten importieren
          </a>
        </h4>
      </div>
      <div id='upload' class='panel-collapse collapse'>
        <div class='panel-body'>
          <form id='uploadFileForm'>
            <div class='form-group'>
              <label for='inputUploadType'>Typ</label>
              <select class='form-control' id='inputUploadType' name='uploadType'>
                <option value='teacher'>Lehrer</option>
                <option value='student'>Schüler</option>
                <option value='logo'>Schul-Logo</option>
                <option value='map'>Lageplan</option>
              </select>
            </div>

            <div class='form-group'>
              <label class='control-label'>Datei auswählen</label>
              <input id='input-file' type='file' name='file' class='file' data-show-preview='false'
                accept='.csv,.odt,.png'>
              <p id="allowed-file-types" class='help-block'>Es sind nur CSV Dateien erlaubt.</p>

              <div id='templateDownloadAlertContainer'></div>
            </div>

            <button type='submit' class='btn btn-primary' id='btn-upload-file'>Importieren</button>
          </form>

          <div class='message' id='uploadFileMessage'></div>

          <div id="csv-preview"></div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#planConference'>
            Sprechtag planen
          </a>
        </h4>
      </div>
      <div id='planConference' class='panel-collapse collapse'>
        <div class='panel-body'>

          <form id='createEventForm'>
            <div class='form-group'>
              <label for='inputName'>Name <i style="font-weight: normal">(wird in der Navigationsleiste
                  angezeigt)</i></label>
              <input type='text' class='form-control' id='inputName' name='name'
                placeholder='Tragen Sie hier den Namen des Elternsprechtags ein'>
            </div>

            <div class='form-group'>
              <label for='inputDate'>Datum</label>
              <div class='input-group input-append date' id='datePicker'>
                <input type='text' class='form-control' id='inputDate' name='date'>
                <span class='input-group-addon'><i class='glyphicon glyphicon-calendar'></i></span>
              </div>
            </div>

            <script>
              $('#datePicker').datepicker({
                container: '#datePicker',
                startDate: '0d',
                autoclose: true,
                format: 'dd.mm.yyyy',
                language: 'de',
                daysOfWeekDisabled: '0,6',
                daysOfWeekHighlighted: '1,2,3,4,5',
                calendarWeeks: true,
                todayHighlight: true
              });
            </script>

            <div class='form-group'>
              <label for='inputStartTime'>Beginn</label>
              <input type='text' class='form-control' id='inputStartTime' name='beginTime'
                placeholder='16:00'>
            </div>

            <div class='form-group'>
              <label for='inputEndTime'>Ende</label>
              <input type='text' class='form-control' id='inputEndTime' name='endTime'
                placeholder='20:00'>
            </div>

            <div class='form-group'>
              <label for='inputSlotDuration'>Dauer einer Einheit</label>
              <select class='form-control' id='inputSlotDuration' name='slotDuration'>
                <option>5</option>
                <option selected>10</option>
                <option>15</option>
                <option>20</option>
              </select>
            </div>

            <div class='form-group'>
              <label for='inputDate'>Buchungsbeginn</label>
              <div class='input-group input-append date' id='datePickerBookingStart'>
                <input type='text' class='form-control' id='startBookingDate' name='startBookingDate'>
                <span class='input-group-addon'><i class='glyphicon glyphicon-calendar'></i></span>
              </div>
            </div>

            <div class='form-group'>
              <label for='inputDate'>Buchungsende</label>
              <div class='input-group input-append date' id='datePickerBookingEnd'>
                <input type='text' class='form-control' id='endBookingDate' name='endBookingDate'>
                <span class='input-group-addon'><i class='glyphicon glyphicon-calendar'></i></span>
              </div>
            </div>

            <div class='form-group'>
              <label for='inputVideoLink'>Videolink</label>
              <input type='text' class='form-control' id='videoLink' name='videoLink'
                placeholder='Falls der Termin nur online stattfindet, bitte Videolink eintragen'>
            </div>

            <label>Pausen</label>
            <div class='radio' id='breakConfig'>
              <label id="noBreaks"><input type='radio' name='breaktype' value='none' checked>
                keine Pausen
              </label> <br />
              <label id="individualBreaks">
                <input type='radio' name='breaktype' value='individual'>
                <span>Individuelle Pausen pro Lehrkraft</span>
                <div>Jede Lehrkraft kann sich bis zu
                  <input name="limit" size="3" value="2" type="number" min="1" max="5">
                  Slots als Pausen reservieren.
                </div>
              </label>
              <br />
              <label id="commonBreaks"><input type='radio' name='breaktype' value='common'>
                Gemeinsame Pausen
                <div>
                  <select class='form-control' name="interval">
                    <option value="3">Pause nach jeder 3. Einheit</option>
                    <option value="4">Pause nach jeder 4. Einheit</option>
                    <option value="5" selected>Pause nach jeder 5. Einheit</option>
                    <option value="6">Pause nach jeder 6. Einheit</option>
                    <option value="7">Pause nach jeder 7. Einheit</option>
                    <option value="8">Pause nach jeder 8. Einheit</option>
                  </select>
                </div>
              </label>
            </div>

            <label>Tägliches Buchungskontingent
              &nbsp;
              <span class='glyphicon glyphicon-question-sign' data-toggle="tooltip" data-placement="top"
                title="Diese Funktion kann ein zu schnelles Ausbuchen aller Termine verhindern. Eltern, die
                                sich erst später um Termine kümmern können, werden dadurch weniger benachteiligt.">
              </span>
            </label>
            <div class="row">
              <div class="col-xs-12 col-sm-4">
                <select name="throttleQuota" id="throttleQuotaSelect" class="form-control">
                  <option value="0">Keine Begrenzung</option>
                  <option value="1">1 Terminbuchung pro Tag</option>
                  <option value="2">2 Terminbuchungen pro Tag</option>
                  <option value="3">3 Terminbuchungen pro Tag</option>
                  <option value="4">4 Terminbuchungen pro Tag</option>
                  <option value="5">5 Terminbuchungen pro Tag</option>
                </select>
              </div>
              <div class="col-xs-5 col-sm-3" style="padding-left: 0">
                <select name="throttleDays" id="throttleDaysSelect" class="hidden form-control">
                  <option value="1">nur am ersten Tag</option>
                  <option value="2">die ersten 2 Tage</option>
                  <option value="3">die ersten 3 Tage</option>
                  <option value="4">die ersten 4 Tage</option>
                  <option value="5">die ersten 5 Tage</option>
                </select>
              </div>
            </div>

            <div class='form-group' style="margin-top: 20px">
              <label><input type='checkbox' name='setActive[]' checked> Diesen Sprechtag als aktiven
                Elternsprechtag
                setzen</label>
            </div>

            <button type='submit' class='btn btn-primary' id='btn-create-event'>Anlegen</button>
          </form>

          <div class='message' id='createEventMessage'></div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#conferenceOverview'>
            Geplante Sprechtage
          </a>
        </h4>
      </div>
      <div id='conferenceOverview' class='panel-collapse collapse'>
        <div class='panel-body'>

          <?php
          $viewController = ViewController::getInstance();
          echo ($viewController->action_getChangeEventForm());
          ?>

          <div class='message' id='changeEventMessage'></div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#attendances'>
            Anwesenheitszeiten
          </a>
        </h4>
      </div>
      <div id='attendances' class='panel-collapse collapse'>
        <div class='panel-body'>
          <div id="activeEventContainer"></div>
          <hr>

          <div class='form-group'>
            <h4>Lehrer</h4>
            <select class='form-control' id='selectTeacher'>
              <option value="">Lehrkraft auswählen</option>
              <?php
              $teachers = UserDAO::getUsersForRole('teacher');
              foreach ($teachers as $teacher): ?>
                <option value='<?php echo $teacher->getId() ?>'>
                  <?php echo (escape($teacher->getLastName() . ' ' . $teacher->getFirstName())) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <hr>

          <div id="changeAttendanceTime"></div>

          <div class='message' id='changeTimeMessage'></div>

          <div id="all-attendances-container">
            <div style="margin: 20px 0" id="all-attendances"></div>
          </div>
          <button onclick="PrintElem('#all-attendances-container', 'Anwesenheiten')">Anwesenheiten
            drucken</button>
        </div>
      </div>
    </div>

    <div id="print-panel" class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#printSchedules'>
            Zeitpläne ausdrucken
          </a>
        </h4>
      </div>
      <div id='printSchedules' class='panel-collapse collapse'>
        <div class='panel-body'>
          <button class="btn btn-primary"
            onclick="PrintElem('#adminTimeTable', '<?php echo escape(getActiveConferenceText()); ?>')">
            <span class='glyphicon glyphicon-print'></span>&nbsp;&nbsp;Zeitpläne aller Lehrkräfte ausdrucken
          </button>

          <div id='adminTimeTable' class="section-to-print only-print"></div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#userManagement'>
            Benutzer
          </a>
        </h4>
      </div>
      <div id='userManagement' class='panel-collapse collapse'>
        <div class='panel-body'>

          <form id='editUsersForm'>
            <div id='changeUserType'>
              <div class='form-group'>
                <div class='radio'>
                  <label><input type='radio' name='changeUserType' value='createUser' checked>
                    neuen Benutzer erstellen
                  </label>
                </div>
                <div class='radio'>
                  <label><input type='radio' name='changeUserType' value='changeUser'>
                    bestehenden Benutzer bearbeiten
                  </label>
                </div>
              </div>
            </div>

            <div id='changeUserForm'></div>
          </form>

          <div class='message' id='changeUserMessage'></div>
        </div>
      </div>
    </div>

    <div id="user-connection" class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#userConnections'>
            Verknüpfte Konten
          </a>
        </h4>
      </div>
      <div id='userConnections' class='panel-collapse collapse'>
        <div class='panel-body'>
          <div id='connectedUsersForm'></div>
          <div id='connectedUsersFeedback'></div>
          <div id="allConnections"></div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#mailTemplates'>
            E-Mail-Vorlagen
          </a>
        </h4>
      </div>
      <div id='mailTemplates' class='panel-collapse collapse'>
        <div class='panel-body'>
          <div class="form-group">
            <label for="selectMailTemplate" class="col-sm-2 control-label"
              style="margin-top: 5px">Vorlage</label>
            <div class="col-sm-10">
              <select class='form-control' id='selectMailTemplate'>
                <option value="">Keine Vorlage ausgewählt</option>
                <option value="bookSlotMailToTeacher">Terminbuchung, E-Mail an Lehrkraft</option>
                <option value="bookSlotMailToStudent">Terminbuchung, E-Mail an Schüler/in</option>
                <option value="slotCancelledByTeacherMailToStudent">Terminstornierung durch Lehrkraft,
                  E-Mail an
                  Schüler/in</option>
                <option value="slotCancelledByStudentMailToTeacher">Terminstornierung durch Schüler/in,
                  E-Mail
                  an
                  Lehrkraft</option>
              </select>
            </div>
            <br><br>

            <div id="templateForm"></div>
            <div id="emailTemplateFeedback"></div>

            <div>
              <div style="padding: 20px 0">
                Folgende Platzhalter können verwendet werden:
              </div>

              <div>
                <strong>{TEACHER_NAME}</strong> für den Namen der Lehrkraft. Titel, Vor- und
                Nachname werden
                eingesetzt.
              </div>
              <div>
                <strong>{STUDENT_NAME}</strong> für den Namen des Schülers. Vor- und Nachname werden
                eingesetzt.
              </div>
              <div>
                <strong>{SLOT_TIME}</strong> für die Uhrzeit des gebuchten/abgesagten Termins.
              </div>
              <div>
                <strong>{CANCELLATION_MESSAGE}</strong> wird im Fall einer Terminabsage mit dem Text
                ersetzt,
                den die Lehrkraft als Grund für die Absage angegeben hat.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#textTemplates'>
            Hinweistexte
          </a>
        </h4>
      </div>
      <div id='textTemplates' class='panel-collapse collapse'>
        <div class='panel-body'>
          <div class="form-group">
            <label for="selectArea" class="col-sm-2 control-label" style="margin-top: 5px">Bereich</label>
            <div class="col-sm-10">
              <select class='form-control' id='selectArea'>
                <option value="">Kein Bereich ausgewählt</option>
                <option value="studentOverview">Übersichtsseite Schüler/Eltern</option>
                <option value="booking">Terminbuchungen Schüler/Eltern</option>
                <option value="teacherOverview">Übersichtsseite Lehrkräfte</option>
              </select>
            </div>
            <br><br>

            <div id="textTemplateForm"></div>
            <div id="textTemplateFeedback"></div>

            <div>
              <div style="padding-top: 20px">
                Die Hinweistexte erscheinen als blauer Kasten oben auf den jeweiligen Seiten. Wenn das
                Textfeld leer bleibt, wird nichts angezeigt.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#passwords'>
            Passwörter
          </a>
        </h4>
      </div>
      <div id='passwords' class='panel-collapse collapse'>
        <?php
        global $LDAP_ENABLED;
        ?>
        <div class='panel-body'>
          <?php if ($LDAP_ENABLED): ?>
            <div>
              Die Passwörter werden über LDAP verwaltet, daher ist dieser Bereich nicht relevant.
              <br>
              Falls dies nicht gewünscht ist, muss in der <strong>config.php</strong> die Variable
              <strong>$LDAP_ENABLED</strong> auf <i>false</i> gesetzt werden.
            </div>
          <?php else: ?>
            <div>
              <h4>Passwörter ausdrucken</h4>
              <div>
                Wenn Sie den Benutzern die Passwörter in papierener Form zukommen lassen wollen,
                können Sie sie hier in kompakter Form ausdrucken.
              </div>
              <div>
                <h5>Schüler-Passwörter</h5>
                <a target="_blank" href="passwords.php?role=student">Zur Druckansicht aller
                  Schüler-Passwörter</a> (sortiert nach Klassen)
              </div>
              <div>
                <h5>Lehrer-Passwörter</h5>
                <a target="_blank" href="passwords.php?role=teacher">Zur Druckansicht aller
                  Lehrer-Passwörter</a>
              </div>
            </div>
            <br>
            <div>
              <h4>Passwörter per E-Mail zusenden</h4>
            </div>
            <div>
              Sie können, sofern Sie es datenschutztechnisch vertreten können, auch alle Passwörter
              an alle Benutzer (Schüler und Lehrer) versenden.
            </div>
            <br>
            <div>
              <button class="btn btn-warning" id="sendAllPasswords">Alle Passwörter per E-Mail
                versenden</button>
            </div>
            <div id="passwordFeedback"></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class='panel panel-default'>
      <div class='panel-heading'>
        <h4 class='panel-title'>
          <a data-toggle='collapse' data-parent='#accordion' href='#statistics'>
            Statistik
          </a>
        </h4>
      </div>
      <div id='statistics' class='panel-collapse collapse'>
        <div class='panel-body'>

          <form id='statisticsForm'>
            <div class='form-group'>
              <label for='selectUserStats'>Benutzer</label>
              <select class='form-control' id='selectUserStats' name='type'>
                <option value="-1">Bitte wähle einen Benutzer ...</option>
                <?php $users = UserDAO::getUsers(); ?>
                <?php foreach ($users as $user): ?>
                  <option value='<?php echo (escape($user->__toString())) ?>'>
                    <?php echo (escape($user->getLastName() . ' ' . $user->getFirstName())) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>

          <div class='message' id='statisticsMessage'></div>

          <div id='statistics'></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once 'inc/footer.php'; ?>