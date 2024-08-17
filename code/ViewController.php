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

        $noSlotsFoundWarning = '<h3>Keine Termine vorhanden!</h3>';
        if ($teacher == null || $user == null || $activeEvent == null) {
            return;
        }

        $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());
        $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());
        $canBook = !$this->checkIfTeacherIsBooked($teacher->getId(), $bookedSlots);
        $room = RoomDAO::getRoomForTeacherId($teacher->getId());

        if (count($slots) <= 0) {
            echo ($noSlotsFoundWarning);
            return;
        }

        // Fetch user data and booked slots for connected accounts, if any
        $connectedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
        $bookedSlotsForConnectedUser = [];
        foreach ($connectedUsers as $cUser) {
            $bookedSlotsForConnectedUser[$cUser->getId()] = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $cUser->getId());
        }

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
                <th width='8%'>Aktion</th>
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
                $bookJson = escape(json_encode(array('slotId' => $slot->getId(), 'teacherId' => $teacher->getId(), 'userId' => $user->getId(), 'eventId' => $activeEvent->getId())));
                ?>

                <?php if ($slot->getType() == 2): ?>
                <tr class='es-time-table-break'>
                    <td><?php echo ($timeTd) ?></td>
                    <td colspan='<?php echo 3 + count($connectedUsers) ?>'>PAUSE</td>
                </tr>
            <?php else: ?>
                <tr class='<?php echo ($teacherAvailable && $studentAvailable && !$timeAlreadyBooked ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                    <td><?php echo ($timeTd) ?></td>
                    <td><?php echo ($teacherAvailable ? '' : 'belegt') ?></td>
                    <td <?php echo !$studentAvailable && $bookedSlots[$fromDate]['teacherName'] == $teacherFullName ? 'class="selectedTeacher"' : '' ?>">
                                <?php echo ($studentAvailable ? '' : $bookedSlots[$fromDate]['teacherName']) ?>
                                </td>

                                <?php foreach ($connectedUsers as $connUser): ?>
                                    <td
                                        class="<?php echo $connectedUserSlotData[$connUser->getId()] == $teacherFullName ? 'selectedTeacher' : 'shadow-cell' ?>">
                                        <?php echo $connectedUserSlotData[$connUser->getId()] ?>
                                    </td>
                                <?php endforeach ?>

                                <td>
                                    <?php if ($teacherAvailable && $studentAvailable && $canBook && !$timeAlreadyBooked): ?>
                                        <button type='button' class='btn btn-primary btn-book' id='btn-book-<?php echo ($slot->getId()) ?>'
                                            value='<?php echo ($bookJson) ?>'>buchen
                                        </button>
                                    <?php endif; ?>
                                </td </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>

                        </tbody>
                        </table>
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

        $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());

        $slots = SlotDAO::calculateSlots($activeEvent, true);
        $rooms = RoomDAO::getAllRooms();

        // Fetch user data and booked slots for connected accounts, if any
        $connectedUsers = UserDAO::getConnectedUsersForUserId($user->getId());
        $bookedSlotsForConnectedUser = [];
        foreach ($connectedUsers as $cUser) {
            $bookedSlotsForConnectedUser[$cUser->getId()] = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $cUser->getId());
        }

        ?>

                        <div id=" printHeader">
                            <h3>Termine von
                                <?php echo ($user->getFirstName() . " " . $user->getLastName()) ?>
                                am
                                <?php echo (toDate($activeEvent->getDateFrom(), 'd.m.Y')) ?>
                            </h3>
                        </div>

                        <table class='table table-hover es-time-table'>
                            <thead>
                                <tr>
                                    <th width='8%'>Uhrzeit</th>
                                    <th width='10%'>Raum</th>
                                    <th width='15%'>
                                        <?php echo count($connectedUsers) == 0 ? 'Mein Zeitplan' : $user->getFirstName() ?>
                                    </th>
                                    <?php foreach ($connectedUsers as $cu): ?>
                                        <th width='15%'><?php echo $cu->getFirstName() ?></th>
                                    <?php endforeach; ?>
                                    <th width='8%' class='no-print'>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach ($slots as $slot):
                                    $fromDate = $slot->getDateFrom();
                                    $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                                    $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));

                                    $roomTd = "";
                                    if (!$studentAvailable && array_key_exists($bookedSlots[$fromDate]['teacherId'], $rooms)) {
                                        $room = $rooms[$bookedSlots[$fromDate]['teacherId']];
                                        $roomTd = escape($room->getRoomNumber()) . optionalBreak() . escape($room->getName());
                                    }

                                    $connectedUserSlotInfo = [];
                                    $siblingAppointmentInSlot = false;
                                    foreach ($connectedUsers as $cu) {
                                        $connUserSlots = $bookedSlotsForConnectedUser[$cu->getId()];
                                        foreach ($connUserSlots as $cus) {
                                            if ($cus["dateFrom"] == $fromDate) {
                                                $connectedUserSlotInfo[$cu->getId()] = $cus;
                                                $siblingAppointmentInSlot = true;

                                                if ($roomTd == "") {
                                                    $room = $rooms[$cus['teacherId']];
                                                    $roomTd = escape($room->getRoomNumber()) . optionalBreak() . escape($room->getName());
                                                } else {
                                                    $roomTd = "Mehrfachbuchung, bitte ändern!";
                                                }
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
                                                <td></td>
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
                                                    <?php echo ($roomTd) ?>
                                                </td>
                                                <td>
                                                    <?php echo ($studentAvailable ? '' : $bookedSlots[$fromDate]['teacherName']) ?>
                                                </td>
                                                <?php foreach ($connectedUsers as $connUser): ?>
                                                    <td>
                                                        <?php echo !isset($connectedUserSlotInfo[$connUser->getId()]) ? "" : $connectedUserSlotInfo[$connUser->getId()]["teacherName"] ?>
                                                    </td>
                                                <?php endforeach ?>

                                                <td class='no-print'>
                                                    <?php if (!$studentAvailable):
                                                        $deleteJson = escape(json_encode(array('userId' => $user->getId(), 'slotId' => $bookedSlots[$fromDate]['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                                                        ?>
                                                        <button type='button' class='btn btn-primary btn-delete'
                                                            id='btn-delete-<?php echo ($bookedSlots[$fromDate]['id']) ?>'
                                                            value='<?php echo ($deleteJson) ?>'>Termin löschen
                                                        </button>
                                                        <?php if (!empty($activeEvent->getVideoLink())):
                                                            $getParam = escape('#userInfo.displayName=%22' . $user->getFirstName() . ' ' . $user->getLastName() . '%22') ?>
                                                            <a class="btn btn-primary btn-delete"
                                                                href="<?php echo ($activeEvent->getVideoLink() . md5($bookedSlots[$fromDate]['id']) . $getParam) ?>"
                                                                target="_blank"> Zum Videomeeting</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
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
        $headerText = "Meine Termine";
        if ($adminPrint) {
            $headerText = "Termine für " . $teacher->getTitle() . " " . $teacher->getFirstName() . " " . $teacher->getLastName();
            $room = RoomDAO::getRoomForTeacherId($teacher->getId());
            if ($room != null) {
                $headerText .= " (Raum: " . $room->getRoomNumber() . " | " . $room->getName() . ")";
            }
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
                                    <th class='colAction no-print'>Aktion</th>
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
                                                    <?php echo ($teacherAvailable ? '' : ($isReservedSlot ? 'RESERVIERT' : $bookedSlots[$fromDate]['studentName'])) ?>
                                                </td>
                                                <?php if (!empty($activeEvent->getVideoLink())):
                                                    $getParam = escape('#userInfo.displayName=%22' . $teacher->getFirstName() . " " . $teacher->getLastName() . "%22"); ?>
                                                    <td><a href="<?php echo ($activeEvent->getVideoLink() . md5($slot->getId()) . $getParam) ?> "
                                                            target=_blank">VideoLink</a></td>
                                                <?php endif; ?>
                                                <td class="colAction no-print">
                                                    <?php if ($isReservedSlot): ?>
                                                        <button class="btn btn-warning es-button-release no-print"
                                                            id="release_<?php echo $slot->getId() ?>" data-slotId="<?php echo $slot->getId() ?>"
                                                            data-eventId="<?php echo $activeEvent->getId() ?>">
                                                            freigeben
                                                        </button>
                                                    <?php elseif (!$teacherAvailable): ?>
                                                        <button class="btn btn-danger es-button-cancel no-print"
                                                            id="cancel_<?php echo $slot->getId() ?>"
                                                            data-teacherId="<?php echo $teacher->getId() ?>"
                                                            data-studentId="<?php echo $slot->getStudentId() ?>"
                                                            data-slotId="<?php echo $slot->getId() ?>"
                                                            data-eventId="<?php echo $activeEvent->getId() ?>">
                                                            Termin verschieben
                                                        </button>

                                                    <?php else: ?>
                                                        <button class="btn btn-warning es-button-reserve no-print"
                                                            id="reserve_<?php echo $slot->getId() ?>" data-slotId="<?php echo $slot->getId() ?>"
                                                            data-eventId="<?php echo $activeEvent->getId() ?>">
                                                            reservieren
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
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

    public function action_getNewsletterForm()
    {
        ?>
                        <form id='newsletterForm'>
                            <?php
                            $checkAccessData = UserDAO::checkAccessData();
                            $activeEventExists = EventDAO::getActiveEvent() != null;
                            $filename = 'uploads/newsletter_filled.odt';
                            $fileExists = file_exists($filename);
                            if ($checkAccessData) {
                                if ($activeEventExists) { ?>
                                    <input type='hidden' id='newsletterExists' value='<?php echo (escape($fileExists)) ?>'>
                                    <button type='button' class='btn btn-primary' id='btn-create-newsletter'>
                                        Rundbrief erzeugen
                                    </button>
                                <?php } else { ?>
                                    <div class='alert alert-info'>
                                        INFO: Es ist momentan kein Elternsprechtag als aktiv gesetzt!<br>
                                        Setze einen Elternsprechtag als aktiv um einen Rundbrief erzeugen zu können!
                                    </div>
                                <?php }
                            } elseif ($fileExists) { ?>
                                <div class='alert alert-info'>
                                    INFO: Um einen neuen Rundbrief zu erstellen, müssen zuerst wieder die Schüler importiert
                                    werden!<br>
                                    (Falls gewünscht kann zuvor auch eine neue Rundbrief-Vorlage hochgeladen werden.)
                                </div>
                            <?php } else { ?>
                                <div class='alert alert-danger'>
                                    Keine Schüler-Zugangsdaten vorhanden! Es müssen zuerst die Schüler importiert werden!
                                </div>
                            <?php } ?>

                            <?php if ($fileExists): ?>
                                <button type='button' class='btn btn-primary' id='btn-delete-newsletter'>
                                    Rundbrief löschen
                                </button>
                            <?php endif; ?>

                            <?php if ($checkAccessData): ?>
                                <button type='button' class='btn btn-primary' id='btn-delete-access-data'>
                                    Schüler-Zugangsdaten löschen
                                </button>
                            <?php endif; ?>

                            <div class='message' id='newsletterMessage'></div>

                            <?php if ($fileExists): ?>
                                <div class='newsletterDownload'>
                                    <p>Rundbrief herunterladen: </p>
                                    <a href='<?php echo ($filename) ?>' type='application/vnd.oasis.opendocument.text'
                                        download>Rundbrief</a>
                                </div>
                            <?php endif; ?>
                        </form>
                        <?php
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
                    Vorname;Nachname;E-Mail;Klasse;Benutzername;Passwort
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein. Benutzername und Passwort sind optional.
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>Angelika;Albers;angie@albers.net;8B;;</li>
                        <li>Britta;Bäcker;britta@baecker.de;1D;baecker1;password1</li>
                    </ul>
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

            case 'newsletter':
            default:
                $typeText = 'Rundbrief Vorlage (ODT)';
                $mimeType = 'application/vnd.oasis.opendocument.text';
                $filePath = 'templates/newsletter_template.odt';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    In der Vorlage können folgende Platzhalter verwendet werden:
                    <ul>
                        <li>ESTODAY (heutiges Datum)</li> 
                        <li>ESDATE (Datum des Elternsprechtags)</li>
                        <li>ESFIRSTNAME (Vorname des Schülers)</li>
                        <li>ESLASTNAME (Nachname des Schülers)</li>
                        <li>ESCLASS (Klasse des Schülers)</li>
                        <li>ESUSERNAME (Benutzername des Schülers)</li>
                        <li>ESPASSWORD (Passwort des Schülers)</li>
                    </ul>
                    </p>';
        }

        ?>
                        <div class='alert alert-info'>
                            <button type='button' class='close' data-dismiss='alert'>&times;</button>
                            <h4>Tipp!</h4>
                            <p><b>Vorlage herunterladen:</b></p>
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
                                    <label for='inputFromTime'>Von</label>
                                    <select class='form-control' id='inputSlotDuration' name='inputFromTime'>
                                        <?php echo (getDateOptions($attendance, true)); ?>
                                    </select>
                                </div>

                                <div class='form-group'>
                                    <label for='inputToTime'>Bis</label>
                                    <select class='form-control' id='inputSlotDuration' name='inputToTime'>
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
                        <p id='activeSpeechdayText'><b>Aktiver Sprechtag:</b>
                            <?php echo escape($displayText); ?>
                        </p>
                        <input type='hidden' id='activeEventId' value='<?php echo escape($activeEventId); ?>'>
                        <?php
    }

    public function action_getSiblingsForm()
    {
        $user = AuthenticationManager::getAuthenticatedUser();
        $alreadyLinkedUserIds = array_map(function ($user) {
            return $user->getId();
        }, UserDAO::getConnectedUsersForUserId($user->getId()));
        $sameLastName = UserDAO::getPossibleSiblings($user->getId(), $user->getLastName());
        ?>
                        <div>Hier kannst Du die Konten Deiner Geschwister verknüpfen, um Dir die Planung einfacher zu machen.
                        </div>
                        <br />
                        <div>Da die Vorschläge nur über den Nachnamen laufen, kann es vorkommen, dass hier Personen gelistet
                            werden, die
                            gar nicht mit Dir verwandt sind. <br />Mit nicht verwandten Personen sollst Du natürlich keine
                            Verknüpfung
                            herstellen.</div>
                        <br />
                        <div class="mt-3">
                            <strong>
                                Die andere Person wird per E-Mail informiert, dass Du sie verknüpft hast.
                            </strong>
                        </div>
                        <?php if (count($sameLastName) == 0 && count($alreadyLinkedUserIds) == 0): ?>
                            <h4>Es wurden keine anderen Personen mit Deinem Nachnamen gefunden.</h4>
                        <?php else: ?>
                            <div class="siblingsForm">
                                <table class="table">
                                    <?php foreach ($sameLastName as $sln): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $sln->getFirstName() . " " . $sln->getLastName() ?></strong>
                                            </td>
                                            <td>
                                                <?php if (in_array($sln->getId(), $alreadyLinkedUserIds)): ?>
                                                    <strong class="text-success"><span class="glyphicon glyphicon-check"></span> bereits
                                                        verknüpft</strong>
                                                <?php else: ?>
                                                    <button id="link_<?php echo $sln->getId() ?>"
                                                        data-studentid="<?php echo $sln->getId() ?>"
                                                        class="btn btn-primary linkStudentBtn"><span
                                                            class="glyphicon glyphicon-link"></span>
                                                        verknüpfen</button>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </table>
                            </div>
                        <?php endif ?>

                        <div>Falls Du Schwierigkeiten bei der Verknüpfung hast oder Deine Geschwister hier nicht findest, melde
                            Dich
                            gerne per Mail an <a href="mailto:admin@dfglfa.net">admin@dfglfa.net</a></div>
                        <?php
    }
}
