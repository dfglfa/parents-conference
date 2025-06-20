<?php
require_once('AuthenticationManager.php');
require_once('Controller.php');
require_once('dao/Entities.php');
require_once('dao/UserDAO.php');
require_once('dao/EventDAO.php');
require_once('dao/SlotDAO.php');

class ViewController extends Controller
{

  public static function getInstance()
  {
    if (!self::$instance || get_class(self::$instance) != "ViewController") {
      self::$instance = new ViewController();
    }
    return self::$instance;
  }

  public function handleGetRequest()
  {
    //check request method
    if (($_SERVER['REQUEST_METHOD'] != 'GET') || (!isset($_REQUEST['action']))) {
      return;
    }

    //execute action
    $method = 'action_' . $_REQUEST['action'];
    $this->$method();
  }

  private function checkIfTeacherIsBooked($teacherId, $bookedSlots)
  {
    foreach ($bookedSlots as $slot) {
      $help = $slot;
      unset($help['eventId']);  //remove eventId, because teacherId could be equal to eventId
      unset($help['id']);  //remove slotId , because teacherId could be equal to slotId
      if (in_array($teacherId, $help)) {
        return true;
      }
    }

    return false;
  }

  public function action_getChangeEventForm()
  {
    $events = EventDAO::getEvents();
    if (count($events) > 0) {
?>
      <form id='changeEventForm'>
        <div class='form-group'>
          <?php

          foreach ($events as $event):
            $display = escape($event->getName() . ' am ' . toDate($event->getDateFrom(), 'd.m.Y'));
            $isActive = $event->isActive() == 1 ? ' checked' : '';
            $id = escape($event->getId());
          ?>
            <div class='radio'>
              <label id="event-label-<?php echo ($id) ?>"><input type='radio' name='eventId'
                  value="<?php echo ($id . '"' . $isActive) ?>><?php echo ($display) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type='submit' class='btn btn-primary btn-change-event' id='btn-change-active-event'>als aktiven Sprechtag setzen</button>
                <button type='submit' class='btn btn-primary btn-change-event' id='btn-delete-event'>Sprechtag löschen</button>
            </form>
            <?php
          } else {
            ?>
            <form id='changeEventForm'>
                <p>Es gibt momentan keinen Elternsprechtag!</p>
            </form>
            <?php
          }
        }

        public function action_getTimeTable()
        {
          $teacher = UserDAO::getUserForId($_REQUEST['teacherId']);
          $user = AuthenticationManager::getAuthenticatedUser();
          $activeEvent = EventDAO::getActiveEvent();
          $teacherBookedByUser = array();

          $noSlotsFoundWarning = '<h3>Keine Termine vorhanden!</h3>';
          if ($teacher == null || $user == null || $activeEvent == null) {
            return;
          }

          $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());
          $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());
          $teacherBookedByUser[$user->getId()] = $this->checkIfTeacherIsBooked($teacher->getId(), $bookedSlots);
          $room = RoomDAO::getRoomForTeacherId($teacher->getId());

          if (count($slots) <= 0) {
            echo ($noSlotsFoundWarning);
            return;
          }

          // Fetch user data and booked slots for connected accounts, if any
          $connectedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
          $bookedSlotsForConnectedUser = [];
          $bookedSlotsCount = count($bookedSlots);
          foreach ($connectedUsers as $cUser) {
            $bookedSlotsForConnectedUser[$cUser->getId()] = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $cUser->getId());
            $teacherBookedByUser[$cUser->getId()] = $this->checkIfTeacherIsBooked($teacher->getId(), $bookedSlotsForConnectedUser[$cUser->getId()]);
            $bookedSlotsCount += count($bookedSlotsForConnectedUser[$cUser->getId()]);
          }

          $bookingQuota = getMaximumNumberOfBookableSlotsUntilCurrentTime();
          $quotaExceeded = $bookingQuota != -1 && $bookingQuota * (count($connectedUsers) + 1) - $bookedSlotsCount <= 0;

          $teacherFullName = $teacher->getTitle() . ' ' . $teacher->getFirstName() . ' ' . $teacher->getLastName();

            ?>
                <h3>Zeitplan von <?php echo $teacherFullName ?></h3>
                <?php if ($room != null): ?>
                    <h4>Raum: <?php echo (escape($room->getRoomNumber()) . ' &ndash; ' . escape($room->getName())) ?></h4>
                <?php endif; ?>

                <table class='table table-hover es-time-table'>
                    <thead>
                        <tr>
                            <th width='8%'>Uhrzeit</th>
                            <th width='15%'><?php echo $teacherFullName ?></th>
                            <th width='15%'><?php echo count($connectedUsers) == 0 ? 'Mein Zeitplan' : $user->getFirstName() ?>
                                <?php foreach ($connectedUsers as $cu): ?>
                                <th width='15%'><?php echo $cu->getFirstName() ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($slots as $slot):
                          $fromDate = $slot->getDateFrom();
                          $teacherAvailable = $slot->getStudentId() == '';
                          $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                          $timeAlreadyBooked = false;

                          $connectedUserSlotData = [];
                          foreach ($connectedUsers as $cu) {
                            $connectedUserSlotData[$cu->getId()] = "";
                            $connUserSlots = $bookedSlotsForConnectedUser[$cu->getId()];
                            foreach ($connUserSlots as $cus) {
                              if ($cus["dateFrom"] == $fromDate) {
                                $connectedUserSlotData[$cu->getId()] = $cus["teacherName"];
                                $timeAlreadyBooked = true;
                                break;
                              }
                            }
                          }

                          $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                          $bookJson = escape(json_encode(array('time' => $slot->getDateFrom(), 'slotId' => $slot->getId(), 'teacherId' => $teacher->getId(), 'userId' => $user->getId(), 'eventId' => $activeEvent->getId())));
                        ?>

                            <?php if ($slot->getType() == 2): ?>
                                <tr class='es-time-table-break'>
                                    <td><?php echo ($timeTd) ?></td>
                                    <td colspan='<?php echo 3 + count($connectedUsers) ?>'>PAUSE</td>
                                </tr>
                            <?php else: ?>
                                <tr
                                    class='<?php echo ($teacherAvailable && $studentAvailable && !$timeAlreadyBooked ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                                    <td><?php echo ($timeTd) ?></td>
                                    <td><?php echo ($teacherAvailable ? '' : 'belegt') ?></td>
                                    <td <?php echo !$studentAvailable && $bookedSlots[$fromDate]['teacherName'] == $teacherFullName ? 'class="selectedTeacher"' : '' ?>">
                <?php if ($teacherAvailable && $studentAvailable && !$teacherBookedByUser[$user->getId()] && !$quotaExceeded): ?>
                  <button type='button' class='btn btn-primary btn-book' id='btn-book-<?php echo ($slot->getId()) ?>'
                    value='<?php echo ($bookJson) ?>'>buchen
                  </button>
                <?php else: ?>
                  <?php echo ($studentAvailable ? '' : $bookedSlots[$fromDate]['teacherName']) ?>
                <?php endif; ?>
                </td>

                <?php foreach ($connectedUsers as $connUser):
                                $bookJson = escape(json_encode(array('slotId' => $slot->getId(), 'teacherId' => $teacher->getId(), 'userId' => $connUser->getId(), 'eventId' => $activeEvent->getId())));
                ?>
                  <td
                    class="<?php echo $connectedUserSlotData[$connUser->getId()] == $teacherFullName ? 'selectedTeacher' : '' ?>">
                    <?php if ($connectedUserSlotData[$connUser->getId()] == '' && $teacherAvailable && !$teacherBookedByUser[$connUser->getId()] && !$quotaExceeded): ?>
                      <button type='button' class='btn btn-primary btn-book' id='btn-book-<?php echo ($slot->getId()) ?>'
                        value='<?php echo ($bookJson) ?>'>buchen
                      </button>
                    <?php else: ?>
                      <?php echo $connectedUserSlotData[$connUser->getId()] ?>
                    <?php endif; ?>

                  </td>
                <?php endforeach ?>
                </tr>
              <?php endif; ?>

            <?php endforeach; ?>

            </tbody>
            </table>
          <?php
        }

        public function action_getMyQuota()
        {
          $user = AuthenticationManager::getAuthenticatedUser();
          $activeEvent = EventDAO::getActiveEvent();
          $bookedSlotsCount = count(SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId()));

          $connectedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
          foreach ($connectedUsers as $cUser) {
            $bookedSlotsCount += count(SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $cUser->getId()));
          }
          $bookingQuota = getMaximumNumberOfBookableSlotsUntilCurrentTime();
          $remainingQuota = $bookingQuota * (count($connectedUsers) + 1) - $bookedSlotsCount;

          ?>
            <?php if ($bookingQuota != -1):
              $date = new DateTime();
              $date->setTimestamp($activeEvent->getStartPostDate());
              $timezone = new DateTimeZone('Europe/Berlin');
              $date->setTimezone($timezone);
              $hour = $date->format('G:i');
              $dailyQuota = $activeEvent->getThrottleQuota();

              $throttleEndDate = new DateTime();
              $throttleEndDate->setTimestamp(getThrottleEndTime());
              $timezone = new DateTimeZone('Europe/Berlin');
              $throttleEndDate->setTimezone($timezone);
            ?>
              <div style="padding-bottom: 20px; font-size:16pt;">
                <?php if ($remainingQuota > 1): ?>
                  <div>Es sind noch <strong class='text-success'><?php echo $remainingQuota ?>
                      Terminbuchungen</strong>
                    möglich.
                  </div>
                <?php elseif ($remainingQuota == 1): ?>
                  <div>Es ist noch <strong class='text-success'>eine Terminbuchung</strong> möglich.</div>
                <?php else: ?>
                  <div class='text-warning'>
                    Ihr Buchungskontingent ist momentan ausgeschöpft.
                  </div>
                <?php endif; ?>

                <div style="font-size: 12pt">
                  Täglich um <?php echo $hour ?> Uhr werden für jeden Schüler
                  <?php echo $dailyQuota == 1 ? 'eine weitere Buchung' : $dailyQuota . ' weitere Buchungen' ?>
                  ermöglicht. Ab dem
                  <strong>
                    <?php echo $throttleEndDate->format('d.m.') . " um " . $throttleEndDate->format('G:i') ?> Uhr
                  </strong>
                  entfällt die
                  Einschränkung.
                </div>
              </div>
            <?php endif; ?>

          <?php

        }
        public function action_getMySlotsTable()
        {
          $typeId = $_REQUEST['typeId'];
          $isFullView = $typeId == 0;

          $user = AuthenticationManager::getAuthenticatedUser();
          $activeEvent = EventDAO::getActiveEvent();

          if ($user == null || $activeEvent == null || $user->getRole() == "admin") {
            return;
          }

          // When we're after the event start time, actions are no longer possible,
          // also improving the overview appearance some more.
          $pastEventStartTime = $activeEvent->getDateFrom() < time();

          $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());

          $slots = SlotDAO::calculateSlots($activeEvent, true);
          $rooms = RoomDAO::getAllRooms();

          // Fetch user data and booked slots for connected accounts, if any
          $connectedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
          $bookedSlotsForConnectedUser = [];
          foreach ($connectedUsers as $cUser) {
            $bookedSlotsForConnectedUser[$cUser->getId()] = SlotDAO::getBookedSlotsForStudent(
              $activeEvent->getId(),
              $cUser->getId()
            );
          }

          ?>

            <?php if (count($connectedUsers) == 0): ?>
              <div id="printHeader">
                <h3>Termine von
                  <?php echo ($user->getFirstName() . " " . $user->getLastName()) ?>
                  am
                  <?php echo (toDate($activeEvent->getDateFrom(), 'd.m.Y')) ?>
                </h3>
              </div>
            <?php endif; ?>

            <table class='table table-hover es-time-table'>
              <thead>
                <tr>
                  <th width='8%'>Uhrzeit</th>
                  <th width='15%'>
                    <?php echo count($connectedUsers) == 0 ? 'Mein Zeitplan' : $user->getFirstName() ?>
                  </th>
                  <?php foreach ($connectedUsers as $cu): ?>
                    <th width='15%'><?php echo $cu->getFirstName() ?></th>
                  <?php endforeach; ?>
                  <?php if (!$pastEventStartTime): ?>
                    <th width='8%' class='no-print'>Aktion</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>

                <?php foreach ($slots as $slot):
                  $fromDate = $slot->getDateFrom();
                  $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                  $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));

                  $connectedUserSlotInfo = [];
                  $siblingAppointmentInSlot = false;
                  foreach ($connectedUsers as $cu) {
                    $connUserSlots = $bookedSlotsForConnectedUser[$cu->getId()];
                    foreach ($connUserSlots as $cus) {
                      if ($cus["dateFrom"] == $fromDate) {
                        $connectedUserSlotInfo[$cu->getId()] = $cus;
                        $siblingAppointmentInSlot = true;
                        break;
                      }
                    }
                  }

                ?>

                  <?php if ($isFullView || !$studentAvailable || $siblingAppointmentInSlot): ?>
                    <?php if ($slot->getType() == 2): ?>
                      <tr class='es-time-table-break'>
                        <td>
                          <?php echo ($timeTd) ?>
                        </td>
                        <td colspan='<?php echo 1 + count($connectedUsers) ?>'>PAUSE</td>
                        <td class='no-print'></td>
                      </tr>
                    <?php else: ?>
                      <tr
                        class='<?php echo ($studentAvailable && !$siblingAppointmentInSlot ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                        <td>
                          <?php echo ($timeTd); ?>
                        </td>
                        <td>
                          <?php if (!$studentAvailable):
                            $_teacher = $bookedSlots[$fromDate];
                            $_room = array_key_exists($bookedSlots[$fromDate]['teacherId'], $rooms) ? $rooms[$_teacher['teacherId']] : null;
                            $deleteJson = escape(json_encode(array('userId' => $user->getId(), 'slotId' => $bookedSlots[$fromDate]['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                          ?>
                            <?php echo $_teacher['teacherName'] ?>
                            <br />
                            <span class="room">
                              <?php if ($_room != null): ?>
                                <?php echo $_room->getRoomNumber() . " " . escape($_room->getName()) ?>
                              <?php endif; ?>
                            </span>
                          <?php endif ?>
                        </td>
                        <?php foreach ($connectedUsers as $connUser): ?>
                          <td>
                            <?php if (isset($connectedUserSlotInfo[$connUser->getId()])):
                              $_teacher = $connectedUserSlotInfo[$connUser->getId()];
                              $_room = array_key_exists($_teacher["teacherId"], $rooms) ? $rooms[$_teacher["teacherId"]] : null;
                            ?>
                              <?php echo $_teacher["teacherName"] ?>
                              <br>
                              <span class="room">
                                <?php if ($_room != null): ?>
                                  <?php echo $_room->getRoomNumber() . " " . escape($_room->getName()) ?>
                                <?php endif ?>
                              </span>
                            <?php endif ?>
                          </td>
                        <?php endforeach ?>

                        <?php if (!$pastEventStartTime): ?>
                          <td class='no-print'>
                            <?php if (!$studentAvailable):
                              $deleteJson = escape(json_encode(array('userId' => $user->getId(), 'slotId' => $bookedSlots[$fromDate]['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                            ?>
                              <button type='button' class='btn btn-warning btn-delete'
                                id='btn-delete-<?php echo ($bookedSlots[$fromDate]['id']) ?>'
                                value='<?php echo ($deleteJson) ?>'>stornieren
                                <?php if (count($connectedUsers) > 0): ?>
                                  (<?php echo $user->getFirstName() ?>)
                                <?php endif ?>
                              </button>
                              <?php if (!empty($activeEvent->getVideoLink())):
                                $getParam = escape('#userInfo.displayName=%22' . $user->getFirstName() . ' ' . $user->getLastName() . '%22') ?>
                                <a class="btn btn-primary btn-delete"
                                  href="<?php echo ($activeEvent->getVideoLink() . md5($bookedSlots[$fromDate]['id']) . $getParam) ?>"
                                  target="_blank"> Zum Videomeeting</a>
                              <?php endif; ?>
                            <?php endif; ?>
                            <?php foreach ($connectedUsers as $connUser): ?>
                              <?php if (isset($connectedUserSlotInfo[$connUser->getId()])):
                                $connUserSlot = $connectedUserSlotInfo[$connUser->getId()];
                                $deleteJson = escape(json_encode(array('userId' => $connUser->getId(), 'slotId' => $connUserSlot['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                              ?>
                                <button type='button' class='btn btn-warning btn-delete' style="margin-top: 5px"
                                  id='btn-delete-<?php echo ($bookedSlots[$fromDate]['id']) ?>'
                                  value='<?php echo ($deleteJson) ?>'>stornieren
                                  (<?php echo $connUser->getFirstName() ?>)
                                </button>
                              <?php endif ?>
                            <?php endforeach ?>
                          </td>
                        <?php endif; ?>
                      </tr>
                    <?php endif; ?>
                  <?php endif; ?>

                <?php endforeach; ?>

              </tbody>
            </table>
            <?php
          }


          public function action_getTeacherTimeTable()
          {
            $typeId = $_REQUEST['typeId'];
            $isFullView = $typeId == 2;

            $teacher = AuthenticationManager::getAuthenticatedUser();

            $this->printTableForTeacher($teacher, $isFullView);
          }

          public function action_getAdminTimeTable()
          {
            $teachers = UserDAO::getUsersForRole('teacher');
            foreach ($teachers as $teacher) {
              $this->printTableForTeacher($teacher, true, true);
            ?>
              <div class="pageBreak"></div>
            <?php
            }
          }

          private function printTableForTeacher($teacher, $isFullView, $adminPrint = false)
          {
            $activeEvent = EventDAO::getActiveEvent();
            
            if ($activeEvent == null) {
              echo "Derzeit ist kein Sprechtag geplant!";
              return;
            }
            $maxIndividualBreaks = $activeEvent->getMaxIndividualBreaks();

            // When we're after the event start time, actions are no longer possible,
            // also improving the overview appearance some more.
            $pastEventStartTime = $activeEvent->getDateFrom() < time();

            $headerText = "Meine Termine";
            if ($adminPrint) {
              $headerText = "Termine für " . $teacher->getTitle() . " " . $teacher->getFirstName() . " " . $teacher->getLastName();
            }

            $room = RoomDAO::getRoomForTeacherId($teacher->getId());
            if ($room != null) {
              $headerText .= " (Raum: " . $room->getRoomNumber() . " | " . $room->getName() . ")";
            }

            ?>
            <div id="printHeader">
              <h3>
                <?php echo escape($headerText); ?>
              </h3>
            </div>
            <?php

            if ($teacher == null || $activeEvent == null) {
              return;
            }

            $bookedSlots = SlotDAO::getBookedSlotsForTeacher($activeEvent->getId(), $teacher->getId());
            $bookedForSelf = 0;
            foreach ($bookedSlots as $s) {
              if ($s['studentId'] == $teacher->getId()) {
                $bookedForSelf++;
              }
            }
            $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());

            ?>
            <table class='table table-hover es-time-table'>
              <thead>
                <tr>
                  <th class='col1'>Uhrzeit</th>
                  <th class='col2'>Schüler</th>
                  <?php if (!empty($activeEvent->getVideoLink())): ?>
                    <th width='10%'>VideoLink</th>
                  <?php endif; ?>
                  <?php if (!$pastEventStartTime): ?>
                    <th class='colAction no-print'>Aktion</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>

                <?php foreach ($slots as $slot):
                  $fromDate = $slot->getDateFrom();
                  $teacherAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                  $isReservedSlot = $slot->getStudentId() == $teacher->getId();
                  $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                ?>

                  <?php if ($isFullView || !$teacherAvailable): ?>
                    <?php if ($slot->getType() == 2): ?>
                      <tr class='es-time-table-break'>
                        <td>
                          <?php echo ($timeTd) ?>
                        </td>
                        <td>PAUSE</td>
                        <td class="no-print"></td>
                        <?php if (!empty($activeEvent->getVideoLink())): ?>
                          <td></td>
                        <?php endif; ?>
                      </tr>
                    <?php else: ?>
                      <tr id='<?php echo "row_" . $slot->getId() ?>'
                        class='<?php echo ($teacherAvailable ? 'es-time-table-available' : ($isReservedSlot ? 'es-time-table-reserved' : 'es-time-table-occupied')) ?>'>
                        <td>
                          <?php echo ($timeTd) ?>
                        </td>
                        <td>
                          <?php echo ($teacherAvailable ? '' : ($isReservedSlot ? 'RESERVIERT / PAUSE' : $bookedSlots[$fromDate]['studentName'])) ?>
                        </td>
                        <?php if (!empty($activeEvent->getVideoLink())):
                          $getParam = escape('#userInfo.displayName=%22' . $teacher->getFirstName() . " " . $teacher->getLastName() . "%22"); ?>
                          <td><a href="<?php echo ($activeEvent->getVideoLink() . md5($slot->getId()) . $getParam) ?> "
                              target=_blank">VideoLink</a></td>
                        <?php endif; ?>

                        <?php if (!$pastEventStartTime): ?>
                          <td class="colAction no-print">
                            <?php if ($isReservedSlot): ?>
                              <button class="btn btn-warning es-button-release no-print"
                                id="release_<?php echo $slot->getId() ?>" data-slotId="<?php echo $slot->getId() ?>"
                                data-eventId="<?php echo $activeEvent->getId() ?>">
                                freigeben
                              </button>
                            <?php elseif (!$teacherAvailable): ?>
                              <button class="btn btn-danger es-button-cancel no-print"
                                id="cancel_slot_<?php echo $slot->getId() ?>"
                                data-teacherId="<?php echo $teacher->getId() ?>"
                                data-studentId="<?php echo $slot->getStudentId() ?>"
                                data-slotId="<?php echo $slot->getId() ?>"
                                data-eventId="<?php echo $activeEvent->getId() ?>">
                                Termin verschieben
                              </button>

                            <?php elseif ($bookedForSelf < $maxIndividualBreaks): ?>
                              <button class="btn btn-warning es-button-reserve no-print"
                                id="reserve_<?php echo $slot->getId() ?>" data-slotId="<?php echo $slot->getId() ?>"
                                data-eventId="<?php echo $activeEvent->getId() ?>">
                                reservieren
                              </button>
                            <?php endif; ?>
                          </td>
                        <?php endif; ?>
                      </tr>
                    <?php endif; ?>

                  <?php endif; ?>

                <?php endforeach; ?>

              </tbody>
            </table>
          <?php
          }

          public function action_createUser()
          {
          ?>

            <?php include_once('inc/userForm.php') ?>

            <button type='submit' class='btn btn-primary' id='btn-create-user'>Benutzer erstellen</button>

          <?php
          }

          public function action_changeUser()
          {
            $users = UserDAO::getUsers();
            $rooms = RoomDAO::getAllRooms();
          ?>

            <div class='form-group'>
              <label for='selectUser'>Benutzer</label>
              <select class='form-control' id='selectUser' name='type'>
                <?php foreach ($users as $user): ?>
                  <?php
                  $val = $user->__toString();
                  if (array_key_exists($user->getId(), $rooms)) {
                    $room = $rooms[$user->getId()];
                    $val = json_decode($user->__toString(), true);
                    $val['roomNumber'] = $room->getRoomNumber();
                    $val['roomName'] = $room->getName();
                    $val['absent'] = $user->isAbsent();
                    $val = json_encode($val);
                  }
                  ?>
                  <option value='<?php echo (escape($val)) ?>'>
                    <?php echo (escape($user->getLastName() . ' ' . $user->getFirstName())) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <hr>

            <?php include_once('inc/userForm.php') ?>

            <button type='submit' class='btn btn-primary' id='btn-edit-user'>Benutzer ändern</button>

            <button type='submit' class='btn btn-primary' id='btn-delete-user'>Benutzer löschen</button>

          <?php
          }

          public function action_stats()
          {
            $userId = $_REQUEST['userId'];
            $logs = LogDAO::getLogsForUser($userId);

          ?>
            <br>
            <form id='deleteStatisticsForm'>
              <button type='button' class='btn btn-primary' id='btn-delete-whole-statistics'>
                gesamte Statistik löschen
              </button>
              <button type='button' class='btn btn-primary'
                id='btn-delete-statistics-for-userId-<?php echo (escape($userId)) ?>'>
                Statistik für ausgewählten Benutzer löschen
              </button>
            </form>
            <br>

            <?php if (count($logs) > 0): ?>
              <table class='table table-hover'>
                <thead>
                  <tr>
                    <th width='16%'>BenutzerID</th>
                    <th width='28%'>Aktion</th>
                    <th width='28%'>Info</th>
                    <th width='28%'>Uhrzeit</th>
                  </tr>
                </thead>
                <tbody>

                  <?php foreach ($logs as $log):
                    $logDate = escape(toDate($log->getDate(), 'd.m.Y H:i:s'));
                    $logInfo = json_decode($log->getInfo(), true);

                    $infoOutput = '';
                    if ($logInfo != null) {
                      $event = EventDAO::getEventForId($logInfo['eventId']);
                      if ($event != null) {
                        if ($log->getAction() == LogDAO::LOG_ACTION_CHANGE_ATTENDANCE) {
                          $infoOutput = 'Sprechtag: ' . escape($event->getName()) .
                            '<br>anwesend von: ' . escape(toDate($logInfo['fromTime'], 'H:i')) .
                            '<br>anwesend bis: ' . escape(toDate($logInfo['toTime'], 'H:i'));
                        } else {
                          $slot = SlotDAO::getSlotForId($logInfo['slotId']);
                          $infoOutput = 'Sprechtag: ' . escape($event->getName()) . '<br>Termin: ' .
                            escape(toDate($slot->getDateFrom(), 'H:i'));
                        }
                      }
                    }
                  ?>

                    <tr>
                      <td>
                        <?php echo (escape($log->getUserId())) ?>
                      </td>
                      <td>
                        <?php echo (getActionString($log->getAction())) ?>
                      </td>
                      <td>
                        <?php echo ($infoOutput) ?>
                      </td>
                      <td>
                        <?php echo ($logDate) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>

                </tbody>
              </table>
            <?php else: ?>
              <p>Es sind keine Statistiken für den ausgewählten Benutzer vorhanden!</p>
            <?php endif;
          }


          public function action_csvPreview()
          {
            $role = $_REQUEST['role'];
            $germanRole = $role == 'student' ? 'Schüler' : 'Lehrer';
            $users = UserDAO::getUsersForRole($role, 10);
            ?>
            <div>
              <h4><br>Die ersten 10 Einträge der importierten
                <?php echo (escape($germanRole)) ?>:
              </h4>
            </div>

            <table class='table table-striped'>
              <tr>
                <th>Benutzername</th>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>E-Mail</th>
                <th>Klasse</th>
              </tr>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <?php echo escape($user->getUserName()); ?>
                  </td>
                  <td>
                    <?php echo escape($user->getFirstName()); ?>
                  </td>
                  <td>
                    <?php echo escape($user->getLastName()); ?>
                  </td>
                  <td>
                    <?php echo escape($user->getEmail()); ?>
                  </td>
                  <td>
                    <?php echo escape($user->getClass()); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php
          }

          public function action_templateDownloadAlert()
          {
            switch ($_REQUEST['type']) {
              case 'student':
                $typeText = 'Schüler Vorlage (CSV)';
                $mimeType = 'text/csv';
                $filePath = 'templates/students.csv';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    Ein Datensatz muss folgende Elemente besitzen:
                    <br>
                    Vorname;Nachname;E-Mail;Klasse;Benutzername;Passwort;Geschwister
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein! Benutzername, Passwort und Geschwister können leer gelassen werden.
                    <br><br>
                    Um ein Geschwisterkind zu verknüpfen, muss der Name des Kindes in der letzten Spalte in der Form NACHNAME, VORNAME angegeben werden.
                    Die Schreibweise des Namens muss exakt übereinstimmen.
                    <br><br>
                    Es genügt, ein Geschwisterkind
                    anzugeben, damit die Verknüpfung zwischen allen Geschwistern erkannt wird. 
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>Eric;Ellinger;ee@foo.de;4A;ellingere;1234567;</li>
                        <li>Franziska;Fürst;ff@foo.de;6A;fuerstf;1234567;</li>
                        <li>Peter;Müller;pm@foo.de;11A;muellerp;1234567;Müller,Anna</li>
                        <li>Anna;Müller;am@foo.de;8A;muellera;1234567;Müller,Peter</li>
                        <li>Hertha;Müller;hm@foo.de;6A;muellerh;1234567;Müller,Peter</li>
                    </ul>
                    <br>
                    Hier sind drei Kinder mit Nachnamen Müller miteinander als Geschwister verknüpft. Am einfachsten ist es, wenn alle Geschwister außer dem ersten
                    den Namen des ersten als Geschwister angeben (sternförmiger Graph). 
                    </p>';
                break;

              case 'teacher':
                $typeText = 'Lehrer Vorlage (CSV)';
                $mimeType = 'text/csv';
                $filePath = 'templates/teachers.csv';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    Ein Datensatz muss folgende Elemente besitzen:
                    <br>
                    Vorname;Nachname;E-Mail;Klasse;Benutzername;Passwort;Titel;Raumnummer;Raumname
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein. Raumnummer und Raumname sind optional.
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>Otto;Normalverbraucher;otto@norm.com;1C;ottonormal;user987;Mag.;A001;Konferenzzimmer</li>
                        <li>John;Doe;jd@foo.bar;2E;johnny456;some_pw!;BEd.;;</li>
                    </ul>
                    </p>';
                break;

              case 'logo':
                $typeText = '';
                $filePath = '';
                $mimeType = 'image/png';
                $infos = 'Das Logo wird auf 30px Höhe herunterskaliert.';
                break;
              case 'map':
                $typeText = '';
                $filePath = '';
                $mimeType = 'image/png';
                $infos = 'Nach dem Upload sehen die SchülerInnen und Eltern den Navigationspunkt "Lageplan", unter dem der Plan zu finden ist.';
                break;
              default:
                $typeText = '';
                $mimeType = '';
                $filePath = '';
                $infos = 'Auswahl nicht definiert.';
            }

          ?>
            <div class='alert alert-info'>
              <button type='button' class='close' data-dismiss='alert'>&times;</button>
              <h4>Tipp!</h4>
              <a href='<?php echo ($filePath) ?>' type='<?php echo ($mimeType) ?>' download>
                <?php echo escape($typeText); ?>
              </a>
              <?php echo ($infos) ?>
            </div>
          <?php
          }

          public function action_attendance()
          {
            $user = AuthenticationManager::getAuthenticatedUser();
            $event = EventDAO::getActiveEvent();

            return $this->getAttendance($user, $event);
          }

          public function action_allAttendances()
          {
            $user = AuthenticationManager::getAuthenticatedUser();
            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $event = EventDAO::getActiveEvent();

            if ($event == null) {
              return "";
            }

            return $this->getAllAttendances($event);
          }

          public function action_attendanceParametrized()
          {
            $userId = $_REQUEST['userId'];
            $eventId = $_REQUEST['eventId'];
            $user = UserDAO::getUserForId($userId);
            $event = EventDAO::getEventForId($eventId);

            return $this->getAttendance($user, $event, true);
          }

          private function getAttendance($user, $event, $named = false)
          {
            $attendance = null;
            $salutation = 'Du bist am ';

            if ($user != null) {
              $attendance = SlotDAO::getAttendanceForUser($user->getId(), $event);
              if ($named) {
                $salutation = $user->getFirstName() . ' ' . $user->getLastName() . ' ist am ';
              }
            }

            if ($attendance != null) {
              if ($attendance['to'] - $attendance['from'] == 0) {
                $output = escape($salutation . date('d.m.Y', $attendance['date']) . ' nicht anwesend.');
              } else {
                $output = escape($salutation . date('d.m.Y', $attendance['date']) . ' von ' . date('H:i', $attendance['from']) . ' bis ' . date('H:i', $attendance['to']) . ' anwesend.');
              }
            } else {
              $output = escape('Es gibt momentan keinen aktuellen Elternsprechtag, für den eine Anwesenheit eingestellt werden könnte.');
            }

            echo $output . '<br><br>';
            return $attendance;
          }
          private function getAllAttendances($event)
          {
            $attendances = SlotDAO::getAttendanceForAllTeachers($event);
            $middleIndex = ceil(count($attendances) / 2);
            $isOdd = count($attendances) % 2 == 1;

            $leftColumn = array_slice($attendances, 0, $middleIndex);
            $rightColumn = array_slice($attendances, $middleIndex);

          ?>
            <table>
              <th>Lehrkraft</th>
              <th>Anwesenheit</th>
              <th class="secondColumnStart">Lehrkraft</th>
              <th>Anwesenheit</th>
              <?php for ($i = 0; $i < $middleIndex - 1; $i++):
                $left = $leftColumn[$i];
                $isCustomAttendanceLeft = $event->getDateFrom() != $left['from'] || $event->getDateTo() != $left['to'];
                $right = $rightColumn[$i];
                $isCustomAttendanceRight = $event->getDateFrom() != $right['from'] || $event->getDateTo() != $right['to'];
              ?>
                <tr>
                  <td>
                    <span id="teacher_<?php echo $left['teacherId'] ?>" class="selectableName">
                      <?php echo $left['lastName'] ?>,
                      <?php echo $left['firstName'] ?>
                    </span>
                  </td>
                  <td style="<?php echo $isCustomAttendanceLeft ? 'font-weight: bold' : ''; ?>">
                    <?php echo toDate($left['from'], 'H:i') ?>-<?php echo toDate($left['to'], 'H:i') ?>
                    Uhr
                  </td>
                  <td class="secondColumnStart">
                    <span id="teacher_<?php echo $right['teacherId'] ?>" class="selectableName">
                      <?php echo $right['lastName'] ?>,
                      <?php echo $right['firstName'] ?>
                    </span>

                  </td>
                  <td style="<?php echo $isCustomAttendanceRight ? 'font-weight: bold' : ''; ?>">
                    <?php echo toDate($right['from'], 'H:i') ?>-<?php echo toDate($right['to'], 'H:i') ?>
                    Uhr
                  </td>
                </tr>
              <?php endfor ?>
              <?php if ($isOdd):
                $lastEntry = $attendances[$middleIndex - 1]; ?>
                <tr>
                  <td>
                    <span id="teacher_<?php echo $lastEntry['teacherId'] ?>" class="selectableName">
                      <?php echo $lastEntry['lastName'] ?>,
                      <?php echo $lastEntry['firstName'] ?>
                    </span>
                  </td>
                  <td>
                    <?php echo toDate($lastEntry['from'], 'H:i') ?>-<?php echo toDate($lastEntry['to'], 'H:i') ?>
                    Uhr
                  </td>
                  <td class="secondColumnStart"></td>
                  <td></td>
                </tr>
              <?php endif; ?>
            </table>
          <?php
          }

          public function action_changeAttendance()
          {
            $userId = $_REQUEST['userId'];
            $eventId = $_REQUEST['eventId'];
            $user = UserDAO::getUserForId($userId);
            $event = EventDAO::getEventForId($eventId);
          ?>
            <h4>
              Aktuelle Anwesenheit
            </h4>
            <p id='attendance'>
              <?php $attendance = $this->getAttendance($user, $event, true); ?>
            </p>

            <?php if ($attendance != null): ?>
              <h4>
                Anwesenheit ändern
              </h4>
              <form id='changeAttendanceForm'>
                <input type='hidden' name='userId' value='<?php echo (escape($userId)) ?>'>
                <input type='hidden' name='eventId' value='<?php echo (escape($attendance['eventId'])) ?>'>
                <div class='form-group'>
                  <select class='form-control' id='inputSlotDuration' name='inputFromTime'
                    style="width: 100px; display: inline-block">
                    <?php echo (getDateOptions($attendance, true)); ?>
                  </select>
                  <label style="padding: 0 20px;">bis</label>
                  <select class='form-control' id='inputSlotDuration' name='inputToTime'
                    style="width: 100px; display: inline-block">
                    <?php echo (getDateOptions($attendance, false)); ?>
                  </select>
                </div>

                <button type='submit' class='btn btn-primary' id='btn-change-attendance'>
                  Anwesenheit für
                  <?php echo escape($user->getFirstName() . ' ' . $user->getLastName()); ?> ändern
                </button>
              </form>
            <?php endif;
          }

          public function action_getActiveEventContainer()
          {
            $event = EventDAO::getActiveEvent();
            $displayText = "kein aktiver Elternsprechtag vorhanden!";
            $activeEventId = -1;
            if ($event != null) {
              $displayText = $event->getName() . ' am ' . toDate($event->getDateFrom(), 'd.m.Y') . ' (mit ' . $event->getSlotTime() . '-Minuten-Intervallen)';
              $activeEventId = $event->getId();
            }
            ?>
            <p id='activeConferenceText'><b>Aktiver Sprechtag:</b>
              <?php echo escape($displayText); ?>
            </p>
            <input type='hidden' id='activeEventId' value='<?php echo escape($activeEventId); ?>'>
          <?php
          }

          public function action_getSiblingsList()
          {
            global $SMTP_FROM;
            $user = AuthenticationManager::getAuthenticatedUser();

            if ($user->getRole() != "student") {
              return;
            }

            $alreadyLinkedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
          ?>
            <?php if (count($alreadyLinkedUsers) == 0): ?>
              <h4>Für Dich sind keine Geschwister eingetragen.</h4>
            <?php else: ?>
              <div>Folgende Benutzer sind als Deine Geschwister eingetragen:
              </div>
              <div class="siblingsForm">
                <table class="table">
                  <?php foreach ($alreadyLinkedUsers as $linkedUser): ?>
                    <tr>
                      <td>
                        <strong><?php echo $linkedUser->getFirstName() . " " . $linkedUser->getLastName() ?></strong>
                      </td>
                      <td>
                        <strong class="text-success"><span class="glyphicon glyphicon-check"></span>
                          verknüpft</strong>
                      </td>
                    </tr>
                  <?php endforeach ?>
                </table>
              </div>
            <?php endif ?>

            <div>Falls Du Deine Geschwister hier nicht findest oder eine falsche Verknüpfung bemerkst, melde
              Dich
              bitte per Mail an <a href="mailto:<?php echo $SMTP_FROM ?>"><?php echo $SMTP_FROM ?></a></div>
          <?php
          }


          public function action_getConnectedUsersForm()
          {
            $user = AuthenticationManager::getAuthenticatedUser();

            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $users = UserDAO::getUsersForRole("student");
          ?>
            <div>
              Wähle zwei Benutzer aus, um ihre Konten zu verknüpfen oder zu trennen.
              <br><br>
            </div>
            <div class='form-group'>
              <div class="row">
                <div class="col-xs-4">
                  <label for='selectUser1'>Benutzer 1</label>
                  <select class='form-control userconnectionSelect' id='selectUser1' name='user1'>
                    <option value="-1">Wähle Benutzer</option>
                    <?php foreach ($users as $user): ?>
                      <option value='<?php echo $user->getId() ?>'>
                        <?php echo (escape($user->getLastName() . ' ' . $user->getFirstName())) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-xs-4">
                  <label for='selectUser2'>Benutzer 2</label>
                  <select class='form-control userconnectionSelect' id='selectUser2' name='user2'>
                    <option value="-1">Wähle Benutzer</option>
                    <?php foreach ($users as $user): ?>
                      <option value='<?php echo $user->getId() ?>'>
                        <?php echo (escape($user->getLastName() . ' ' . $user->getFirstName())) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div>
                    <input style="padding: 10px 5px 0 0; margin: 5px 5px 0 0;" type="checkbox"
                      id="filterSameName" name="filterSameName" />
                    <label for="filterSameName">nur gleiche Nachnamen</label>
                  </div>
                </div>
              </div>
            </div>
          <?php
          }

          public function action_getConnections()
          {
            $user = AuthenticationManager::getAuthenticatedUser();

            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $connections = UserDAO::getAllConnections();

          ?>

            <h4>Bereits verknüpfte Benutzer:</h4>
            <div id="toggleFeedback"></div>
            <table>
              <?php foreach ($connections as $conn): ?>
                <tr>
                  <td><?php echo $conn['lastName1'] . ", " . $conn['firstName1'] ?></td>
                  <td><?php echo $conn['lastName2'] . ", " . $conn['firstName2'] ?></td>
                  <td><button class="btn btn-link editConnectionBtn" data-userid1="<?php echo $conn['userId1'] ?>"
                      data-userid2="<?php echo $conn['userId2'] ?>">bearbeiten</button></td>
                </tr>
              <?php endforeach ?>

            </table>

          <?php
          }

          public function action_checkUserConnection()
          {
            $user = AuthenticationManager::getAuthenticatedUser();
            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $userId1 = $_REQUEST['userId1'];
            $userId2 = $_REQUEST['userId2'];

            $transitiveConnection = false;
            $siblings = UserDAO::getConnectedUsersForUserId($userId1);
            foreach ($siblings as $sib) {
              if ($sib->getId() == $userId2) {
                $transitiveConnection = true;
                break;
              }
            }
            $directConnection = UserDAO::areUsersDirectlyConnected($userId1, $userId2);

          ?>
            <?php if ($directConnection): ?>
              <div>Die Benutzer sind verknüpft. &nbsp; <button class="btn btn-danger"
                  id="userconnectionAction">trennen</button> </div>
            <?php elseif ($transitiveConnection): ?>
              <div>Die Benutzer sind indirekt verknüpft.</div>
            <?php else: ?>
              <div>Die Benutzer sind noch nicht verknüpft. &nbsp; <button class="btn btn-success"
                  id="userconnectionAction">verknüpfen</button> </div>
            <?php endif ?>
          <?php
          }

          public function action_mailTemplateForm()
          {
            $user = AuthenticationManager::getAuthenticatedUser();
            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $templateId = $_REQUEST['templateId'];
            $data = getDataForJsonTemplate($templateId);

            if ($data == null) {
              return;
            }

          ?>
            <div>

              <input type="hidden" name="templateId" id="templateId" value="<?php echo $templateId ?>" />
              <div class="form-group">
                <label for="subject" class="col-sm-2 control-label" style="margin-top: 5px">Betreff</label>
                <div class="col-sm-10">
                  <input value="<?php echo $data['subject'] ?>" type="text" class="form-control"
                    id="emailTemplateSubject" placeholder="Betreff eingeben" name="emailTemplateSubject">
                </div>
              </div>
              <br><br>
              <div class="form-group">
                <label for="body" class="col-sm-2 control-label" style="margin-top: 5px">E-Mail-Text
                  <br><span style="font-weight: normal; font-size: small">(HTML erlaubt)</span>
                </label>
                <div class="col-sm-10" style="padding-bottom: 20px">
                  <textarea class="form-control" id="emailTemplateContent" rows="8"
                    name="emailTemplateContent"
                    placeholder="E-Mail-Text eingeben"><?php echo str_replace("<br>", "\n", $data['content']) ?></textarea>
                </div>
              </div>
            </div>
            <button class="btn btn-primary" id="saveTemplateButton" onClick="saveEmailTemplate()">Vorlage
              speichern</button>
          <?php
          }

          public function action_textTemplateForm()
          {
            $user = AuthenticationManager::getAuthenticatedUser();
            if ($user->getRole() != "admin") {
              return "Unauthorized";
            }

            $templateId = $_REQUEST['templateId'];
            $data = getDataForJsonTemplate($templateId);

            if ($data == null) {
              error_log("Template not found: " . $templateId);
              return;
            }

          ?>
            <div>
              <input type="hidden" name="textTemplateId" id="textTemplateId" value="<?php echo $templateId ?>" />
              <div class="form-group">
                <label for="body" class="col-sm-2 control-label" style="margin-top: 5px">Hinweistext
                  <br><span style="font-weight: normal; font-size: small">(HTML erlaubt)</span>
                </label>
                <div class="col-sm-10" style="padding-bottom: 20px">
                  <textarea class="form-control" id="textTemplateContent" rows="8" name="textTemplateContent"
                    placeholder="Hinweistext eingeben"><?php echo str_replace("<br>", "\n", $data['content']) ?></textarea>
                </div>
              </div>
            </div>
            <button class="btn btn-primary" id="saveTextTemplateButton" onClick="saveTextTemplate()">Hinweistext
              speichern</button>
        <?php
          }
        }
