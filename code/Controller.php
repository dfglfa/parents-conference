<?php
require_once('AuthenticationManager.php');
require_once('dao/UserDAO.php');
require_once('dao/EventDAO.php');
require_once('dao/SlotDAO.php');
require_once('dao/LogDAO.php');
require_once('dao/RoomDAO.php');
require_once('dao/MessageDAO.php');
require_once('config.php');
require_once('Notification.php');

class Controller
{
    // request wide singleton
    protected static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Controller();
        }
        return self::$instance;
    }

    public function handlePostRequest()
    {
        //check request method
        if (($_SERVER['REQUEST_METHOD'] != 'POST') || (!isset($_REQUEST['action']))) {
            return;
        }

        //execute action
        $method = 'action_' . $_REQUEST['action'];
        $this->$method();
    }

    protected function forward($errors = null, $target = null)
    {
        if ($target == null) {
            if (!isset($_REQUEST['page'])) {
                throw new Exception('Missing target for forward!');
            }
            $target = strtok($_REQUEST['page'], '?');
        }
        // forward request to target
        require($_SERVER['DOCUMENT_ROOT'] . $target);
        exit(0); // --> successful termination of script
    }

    //=== USER ACTIONS ===
    protected function action_createEvent()
    {
        $name = $_REQUEST['name'];
        $date = $_REQUEST['date'];
        $beginTime = $_REQUEST['beginTime'];
        $endTime = $_REQUEST['endTime'];
        $slotDuration = $_REQUEST['slotDuration'];
        $setActive = $_REQUEST['setActive'] == 'true' ? true : false;
        $startBookingDate = $_REQUEST['startBookingDate'];
        $endBookingDate = $_REQUEST['endBookingDate'];
        $videoLink = $_REQUEST['videoLink'];
        $breaks = $_REQUEST['breaks'];
        $throttleQuota = $_REQUEST['throttleQuota'];
        $throttleDays = $_REQUEST['throttleDays'];

        $unixTimeFrom = strtotime($date . ' ' . $beginTime);
        $unixTimeTo = strtotime($date . ' ' . $endTime);

        // Making the bootstrap datepicker tz-aware is just too much hassle ... 
        // We assume the we are in timezone Europe/Berlin in winter time, so we subtract one hour.
        $startPostDate = strtotime($startBookingDate) - 3600;
        $finalPostDate = strtotime($endBookingDate) - 3600;

        if (!$unixTimeFrom || !$unixTimeTo) {
            return;
        }

        $eventId = EventDAO::createEvent($name, $unixTimeFrom, $unixTimeTo, $slotDuration, $setActive, $startPostDate, $finalPostDate, $videoLink, $breaks, $throttleDays, $throttleQuota);
        if ($eventId > 0) {
            echo 'success';
        }
    }

    protected function action_changeAttendance()
    {
        $fromTime = $_REQUEST['inputFromTime'];
        $toTime = $_REQUEST['inputToTime'];
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];

        if ($toTime < $fromTime) {
            echo 'failure';
            return;
        }

        $authUser = AuthenticationManager::getAuthenticatedUser();
        $event = EventDAO::getActiveEvent();

        if ($event == null || $event->getStartPostDate() < time() && $authUser->getRole() != "admin") {
            echo "Changes forbidden, booking already started!";
            return;
        }

        SlotDAO::changeAttendanceForUser($userId, $eventId, $fromTime, $toTime);

        echo 'success';
    }

    protected function action_uploadFile()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        header('Content-Type: text/html; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!array_key_exists('file-0', $_FILES)) {
                echo 'Es wurde keine Datei ausgewählt!';
                return;
            }

            $name = $_FILES['file-0']['name'];
            $tmpName = $_FILES['file-0']['tmp_name'];
            $error = $_FILES['file-0']['error'];
            $size = $_FILES['file-0']['size'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            switch ($error) {
                case UPLOAD_ERR_OK:
                    //validate file size
                    if ($size / 1024 / 1024 > 2) {
                        echo 'Die Datei überschreitet die Maximalgröße!';
                        return;
                    }

                    //upload file
                    $type = $_REQUEST['uploadType'];
                    if (in_array($type, array('student', 'teacher', 'subject'))) {
                        if (!$this->validateFileExtension($ext, array('csv'))) {
                            echo 'Ungültiges Dateiformat!';
                            return;
                        }
                        $targetPath = $this->uploadFileAs($name, $tmpName);
                        $importCSVResult = $this->importCSV($type, $targetPath);
                        echo $importCSVResult['success'] ? 'success' : $importCSVResult['message'];
                        return;

                    } else if ($type == 'logo') {
                        if (!$this->validateFileExtension($ext, array('png'))) {
                            echo 'Ungültiges Dateiformat!';
                            return;
                        }
                        $this->uploadFileAs('logo.png', $tmpName, "public");
                        echo 'success';
                        return;
                    } else {
                        echo 'Ungültiger Typ!';
                        return;
                    }

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo 'Die Datei überschreitet die Maximalgröße!';
                    return;

                case UPLOAD_ERR_PARTIAL:
                    echo 'Die Datei konnte nicht vollständig hochgeladen werden!';
                    return;

                case UPLOAD_ERR_NO_FILE:
                    echo 'Es wurde keine Datei ausgewählt!';
                    return;

                case UPLOAD_ERR_NO_TMP_DIR:
                    echo 'Kein Ordner für den Dateiupload verfügbar!';
                    return;

                case UPLOAD_ERR_CANT_WRITE:
                    echo 'Die Datei konnte nicht auf den Server geschrieben werden!';
                    return;

                case UPLOAD_ERR_EXTENSION:
                    echo 'Der Dateiupload wurde durch eine Erweiterung abgebrochen!';
                    return;

                default:
                    echo 'Die Datei konnte nicht hochgeladen werden!';
                    return;
            }
        }
    }

    private function checkCSVHeader($type, $row)
    {
        $constraints['teacher'] = array('Vorname', 'Nachname', 'E-Mail', 'Klasse', 'Benutzername', 'Passwort', 'Titel', 'Raumnummer', 'Raumname');
        $constraints['student'] = array('Vorname', 'Nachname', 'E-Mail', 'Klasse', 'Benutzername', 'Passwort', 'Geschwister');
        $constraints['subject'] = array('ToDo');

        $constraintPart = implode('', $constraints[$type]);
        $length = strlen($constraintPart);
        if (substr(implode('', $row), 0 - $length) == substr($constraintPart, 0 - $length)) {
            return true;
        } else {
            echo (substr(implode('', $row), 0 - $length) . " / " . substr($constraintPart, 0 - $length));
            return false;
        }
    }

    private function removeSpecials($string)
    {
        $search = array('ç', 'æ', 'œ', 'á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ì', 'ò', 'ù', 'ä', 'ë', 'ï', 'ö', 'ü', 'ÿ', 'â', 'ê', 'î', 'ô', 'û', 'å', 'ø', 'ß', 'Ä', 'Ö', 'Ü');
        $replace = array('c', 'ae', 'oe', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'ae', 'e', 'i', 'oe', 'ue', 'y', 'a', 'e', 'i', 'o', 'u', 'a', 'o', 'ss', 'Ae', 'Oe', 'Ue');
        return str_replace($search, $replace, $string);
    }

    private function generateUserName($firstName, $lastName, $digits = 3)
    {
        $randomDigit = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        $firstName = strtolower($this->removeSpecials(preg_replace('/\s/', '', $firstName)));
        $lastName = strtolower($this->removeSpecials(preg_replace('/\s/', '', $lastName)));

        return substr($lastName, 0, 3) . substr($firstName, 0, 3) . $randomDigit;
    }

    private function generateRandomPassword($length = 10)
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789!@#$%&*()_-=+,.?';
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    protected function uploadFileAs($name, $tmpName, $folder = "uploads")
    {
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $targetPath = $folder . DIRECTORY_SEPARATOR . $name;
        move_uploaded_file($tmpName, $targetPath);
        return $targetPath;
    }

    protected function importCSV($role, $targetPath)
    {
        // import into database
        $filename = $targetPath;
        $fp = fopen($filename, 'r');

        //parse the csv file row by row
        $firstRow = true;
        $users = array();
        $accessData = array();
        $rooms = array();
        $userNames = array();
        $userConnections = array();

        $duplicateUserError = array(
            'success' => false,
            'message' => 'Die Benutzernamen sind nicht eindeutig! Bitte vergib eindeutige Benutzernamen!'
        );

        $csv = file_get_contents($filename);
        $isUTF8 = mb_detect_encoding($csv, mb_detect_order(), TRUE) == 'UTF-8';

        while (($row = fgetcsv($fp, 0, ';')) != FALSE) {
            if (!$isUTF8) {
                $row = array_map('utf8_encode', $row);
            }

            if ($firstRow) {
                if (!$this->checkCSVHeader($role, $row)) {
                    fclose($fp);
                    return array(
                        'success' => false,
                        'message' => 'Die Spalten der CSV Datei passen nicht zum gewählten Typ!'
                    );
                } else {
                    $firstRow = false;
                }
            } else {
                //insert csv data into mysql table
                $email = trim($row[2]);
                $class = trim($row[3]) != '' ? trim($row[3]) : null;

                if ($role == 'teacher') {
                    $userName = trim($row[4]);
                    $password = trim($row[5]);

                    if (!$this->checkForUniqueUserName($userName, $userNames)) {
                        fclose($fp);
                        return $duplicateUserError;
                    }
                    $userNames[] = $userName;
                    $title = trim($row[6]);

                    $roomNumber = trim($row[7]);
                    $roomName = trim($row[8]);
                    if ($roomNumber != '' && $roomName != '') {
                        $rooms[$userName] = array($roomNumber, $roomName);
                    }
                } elseif ($role == 'student') {
                    $userName = trim($row[4]);

                    $tries = 0;
                    if ($userName == '') {
                        do {
                            $userName = $this->generateUserName(trim($row[0]), trim($row[1]));
                            $tries++;
                        } while ((!$this->checkForUniqueUserName($userName, $userNames)) && ($tries < 500));
                    }
                    if (!$this->checkForUniqueUserName($userName, $userNames)) {
                        fclose($fp);
                        return $duplicateUserError;
                    }
                    $userNames[] = $userName;
                    $title = '';

                    $password = trim($row[5]) == '' ? $this->generateRandomPassword() : trim($row[5]);

                    if (trim($row[6] != '')) {
                        //error_log("Found sibling " . $row[6] . " of username " . $userName);
                        $userConnections[$userName] = trim($row[6]);
                    }

                    $accessData[] = array($userName, $password);
                } else {
                    return array(
                        'success' => false,
                        'message' => 'Unbekannter Typ "' . $role . '"'
                    );
                }

                $users[] = array($userName, createPasswordHash($password), trim($row[0]), trim($row[1]), $email, $class, $role, $title);
            }
        }

        $deleteExistingDataSuccess = UserDAO::deleteUsersByRole($role);
        if ($role == 'teacher') {
            $deleteExistingDataSuccess = $deleteExistingDataSuccess && EventDAO::deleteAllEvents() && RoomDAO::deleteAllRooms();
        } elseif ($role == 'student') {
            $deleteExistingDataSuccess = $deleteExistingDataSuccess && UserDAO::deleteAllConnections();
        }

        if (!$deleteExistingDataSuccess) {
            fclose($fp);
            return array(
                'success' => false,
                'message' => 'Die bestehenden Einträge des gewählten Typs konnten nicht gelöscht werden!'
            );
        }

        UserDAO::bulkInsertUsers($users, $rooms);
        if (count($accessData) > 0) {
            UserDAO::bulkInsertAccessData($accessData);
        }

        if ($role == 'student') {
            UserDAO::bulkConnectUsers($userConnections);
        }

        fclose($fp);
        return array(
            'success' => true,
            'message' => 'Die CSV Datei wurde erfolgreich importiert!'
        );
    }

    private function checkForUniqueUserName($userName, $userNames)
    {
        return !in_array($userName, $userNames);
    }

    protected function validateFileExtension($ext, $allowed)
    {
        if (!in_array($ext, $allowed)) {
            return false;
        }

        return true;
    }

    protected function action_changeSlot()
    {
        $slotId = $_REQUEST['slotId'];
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];

        $authUser = AuthenticationManager::getAuthenticatedUser();
        if ($authUser->getId() != $userId) {
            // Possibly unauthorized. Sibling access might be allowed
            $isSibling = false;
            foreach (UserDAO::getConnectedUsersForUserId($authUser->getId()) as $cu) {
                if ($cu->getId() == $userId) {
                    // connected sibling found => OK
                    $isSibling = true;
                    break;
                }
            }
            if (!$isSibling) {
                echo "Unauthorized!";
                return;
            }
        }

        $info = json_encode(array('eventId' => $eventId, 'slotId' => $slotId));
        LogDAO::log($userId, LogDAO::LOG_ACTION_BOOK_SLOT, $info);

        $result = SlotDAO::setStudentToSlot($eventId, $slotId, $userId);
        if ($result['success']) {
            if ($result['rowCount'] > 0) {
                sendCreationNotificationMail($slotId);
                echo ('success');
            } else {
                echo ('dirtyRead');
            }
        } else {
            echo ('error');
        }
    }

    protected function action_deleteSlot()
    {
        $slotId = $_REQUEST['slotId'];
        $eventId = $_REQUEST['eventId'];

        $authUser = AuthenticationManager::getAuthenticatedUser();

        $info = json_encode(array('eventId' => $eventId, 'slotId' => $slotId));
        LogDAO::log($authUser->getId(), LogDAO::LOG_ACTION_DELETE_SLOT, $info);

        if ($authUser->getRole() == "teacher") {
            $reasonText = $_REQUEST['reasonText'];
            sendCancellationNotificationMailToStudent($slotId, $reasonText);
        } else {
            sendCancellationNotificationMailToTeacher($slotId);
        }
        $success = SlotDAO::deleteStudentFromSlot($eventId, $slotId);

        if ($success) {
            echo ('success');
        } else {
            echo ('error');
        }
    }

    protected function action_setActiveEvent()
    {
        $eventId = $_REQUEST['eventId'];

        $success = EventDAO::setActiveEvent($eventId);

        if ($success) {
            echo ('success');
        } else {
            echo ('error');
        }
    }

    protected function action_deleteEvent()
    {
        $eventId = $_REQUEST['eventId'];

        $success = EventDAO::deleteEvent($eventId);

        if ($success) {
            echo ('success');
        } else {
            echo ('error');
        }
    }

    protected function action_createUser()
    {
        $userName = $_REQUEST['userName'];
        $password = $_REQUEST['password'];
        $firstName = $_REQUEST['firstName'];
        $lastName = $_REQUEST['lastName'];
        $email = $_REQUEST['email'];
        $class = $_REQUEST['class'];
        $type = $_REQUEST['type'];
        $roomNumber = $_REQUEST['roomNumber'];
        $roomName = $_REQUEST['roomName'];

        $userId = UserDAO::register($userName, $password, $firstName, $lastName, $email, $class, $type);
        $updateRoomResult = true;
        if ($roomNumber != '' && $roomName != '') {
            $updateRoomResult = RoomDAO::update($roomNumber, $roomName, $userId)['success'];
        }

        if (($userId > 0) && $updateRoomResult) {
            echo ('success');
        } else if ($userId == -1) {
            echo ('Der Benutzer existiert bereits!');
        } else {
            echo ('Das Passwort muss mindestens ' . UserDAO::MIN_PASSWORD_LENGTH . ' Zeichen lang sein!');
        }
    }

    protected function action_editUser()
    {
        $userId = $_REQUEST['userId'];
        $userName = $_REQUEST['userName'];
        $password = $_REQUEST['password'];
        $firstName = $_REQUEST['firstName'];
        $lastName = $_REQUEST['lastName'];
        $email = $_REQUEST['email'];
        $class = $_REQUEST['class'];
        $type = $_REQUEST['type'];
        $roomNumber = $_REQUEST['roomNumber'];
        $roomName = $_REQUEST['roomName'];

        $updateUserResult = UserDAO::update($userId, $userName, $password, $firstName, $lastName, $email, $class, $type);
        $updateRoomResult = true;
        if ($roomNumber != '' && $roomName != '') {
            $updateRoomResult = RoomDAO::update($roomNumber, $roomName, $userId)['success'];
        }
        if (isset($_REQUEST['absent'])) {
            UserDAO::updateAbsent($userId, true);
        }

        if ($updateUserResult && $updateRoomResult) {
            echo ('success');
        } else {
            echo ('error');
        }
    }

    protected function action_deleteUser()
    {
        $userId = $_REQUEST['userId'];

        $deleteUserResult = UserDAO::deleteUserById($userId);

        if ($deleteUserResult) {
            echo ('success');
        } else {
            echo ('error');
        }
    }


    protected function action_deleteAccessData()
    {
        $deleteSuccess = UserDAO::deleteAccessData();

        if ($deleteSuccess) {
            echo 'success';
        } else {
            echo 'Die Schüler-Zugangsdaten konnten nicht gelöscht werden!';
        }
    }

    protected function action_deleteStats()
    {
        $userId = $_REQUEST['userId'];

        if ($userId != -1) {
            $success = LogDAO::deleteStatsForUser($userId);
        } else {
            $success = LogDAO::deleteAllStats();
        }

        if ($success) {
            echo 'success';
        } else {
            echo 'failure';
        }
    }

    protected function action_createCancellationMessage()
    {
        $teacherId = $_REQUEST['teacherId'];
        $studentId = $_REQUEST['studentId'];
        $slotId = $_REQUEST['slotId'];
        $reasonText = $_REQUEST['reasonText'];

        $slot = SlotDAO::getSlotForId($slotId);

        if ($slot->getTeacherId() != $_SESSION['userId']) {
            echo 'Unauthorized';
            return;
        }

        $teacher = UserDAO::getUserForId($teacherId);
        $messageText = $teacher->getFirstName() . " " . $teacher->getLastName() . " möchte den Termin mit Ihnen verschieben. ";

        if (!empty($reasonText)) {
            $messageText .= "Es wurde folgender Kommentar von der Lehrkraft hinterlegt: <strong>\"" . $reasonText . "</strong>\"";
        } else {
            $messageText .= " Es wurde keine Begründung angegeben.";
        }

        MessageDAO::createMessage($teacherId, $studentId, $messageText);

        ?>

        <td colspan="3">
            Der Termin wurde abgesagt. Der Schüler wird per E-Mail informiert.
        </td>

        <?php
    }

    protected function action_dismissMessage()
    {
        $messageId = $_REQUEST['messageId'];
        $receiverId = $_REQUEST['receiverId'];

        if ($receiverId != $_SESSION['userId']) {
            echo "Unauthorized";
            return;
        }

        MessageDAO::deleteMessageForReceiverId($messageId, $receiverId);
    }

    private function normalize($s)
    {
        return strtolower(str_replace(str_replace($s, " ", ""), "-", ""));
    }

    protected function action_reserveSlot()
    {
        $user = AuthenticationManager::getAuthenticatedUser();
        $slotId = $_REQUEST['slotId'];
        $eventId = $_REQUEST['eventId'];

        if (SlotDAO::getSlotForId($slotId)->getTeacherId() != $user->getId()) {
            error_log("Slot access denied");
            return;
        }

        SlotDAO::setStudentToSlot($eventId, $slotId, $user->getId());
    }

    protected function action_releaseSlot()
    {
        $user = AuthenticationManager::getAuthenticatedUser();
        $slotId = $_REQUEST['slotId'];
        $eventId = $_REQUEST['eventId'];

        if (SlotDAO::getSlotForId($slotId)->getTeacherId() != $user->getId()) {
            error_log("Slot access denied");
            return;
        }

        SlotDAO::deleteStudentFromSlot($eventId, $slotId);
    }

    public function action_toggleUserConnection()
    {
        $user = AuthenticationManager::getAuthenticatedUser();
        if ($user->getRole() != "admin") {
            return "Unauthorized";
        }

        $userId1 = $_REQUEST['userId1'];
        $userId2 = $_REQUEST['userId2'];

        $directConnection = UserDAO::areUsersDirectlyConnected($userId1, $userId2);

        if ($directConnection) {
            UserDAO::disconnectUsers($userId1, $userId2);
        } else {
            UserDAO::connectUsers($userId1, $userId2);
        }
    }

    public function action_saveEmailTemplate()
    {
        $authUser = AuthenticationManager::getAuthenticatedUser();
        if ($authUser->getRole() != "admin") {
            echo "Unauthorized";
            return;
        }

        $templateId = $_REQUEST['templateId'];
        $data = array(

            "subject" => $_REQUEST['subject'],
            "content" => $_REQUEST['content']
        );

        if (getDataForMailTemplate($templateId) == null) {
            echo "Unknown template ID: " . $templateId;
            return;
        }

        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        $filePath = "uploads/" . $templateId . ".json";

        file_put_contents($filePath, $jsonData);
    }
}
