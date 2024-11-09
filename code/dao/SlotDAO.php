<?php

require_once('AbstractDAO.php');
require_once('Entities.php');
require_once('UserDAO.php');

class SlotDAO extends AbstractDAO
{

    public static function getSlotForId($slotId)
    {
        $slot = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, eventId, teacherId, studentId, dateFrom, dateTo, type, available FROM slot WHERE id = ?;', array($slotId));

        if ($s = self::fetchObject($res)) {
            $slot = new Slot($s->id, $s->eventId, $s->teacherId, $s->studentId, $s->dateFrom, $s->dateTo, $s->type, $s->available);
        }
        self::close($res);
        return $slot;
    }

    public static function createSlotsForEvent($eventId, $teachers)
    {
        $event = EventDAO::getEventForId($eventId);
        $slots = self::calculateSlots($event);

        $sth = self::getConnection()->prepare('INSERT INTO `slot`(`eventId`, `teacherId`, `dateFrom`, `dateTo`, `type`, `available`) VALUES (?, ?, ?, ?, ?, ?);');
        $sth->bindValue(1, $eventId);
        $sth->bindValue(6, 1);

        foreach ($teachers as $teacher) {
            $sth->bindValue(2, $teacher->getId());
            foreach ($slots as $slot) {
                $sth->bindValue(3, $slot['start']);
                $sth->bindValue(4, $slot['end']);
                $sth->bindValue(5, $slot['type']);
                $sth->execute();
            }
        }

        return true;
    }

    public static function changeAttendanceForUser($userId, $eventId, $fromTime, $toTime)
    {
        $con = self::getConnection();

        self::getConnection()->beginTransaction();
        self::query($con, 'UPDATE slot SET available = 1 WHERE teacherId = ? AND eventId = ?;', array($userId, $eventId));
        self::query($con, 'UPDATE slot SET available = 0, studentId = NULL WHERE teacherId = ? AND eventId = ? AND (dateFrom NOT BETWEEN ? AND ? - 1);', array($userId, $eventId, $fromTime, $toTime));
        self::getConnection()->commit();

        $info = json_encode(array('eventId' => $eventId, 'fromTime' => $fromTime, 'toTime' => $toTime));
        LogDAO::log($userId, LogDAO::LOG_ACTION_CHANGE_ATTENDANCE, $info);
    }

    public static function getAttendanceForUser($userId, $event)
    {
        if ($event == null) {
            return null;
        }

        $attendance = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT MIN(dateFrom) AS `from`, MAX(dateTo) AS `to` FROM slot WHERE eventId = ? AND teacherId = ? AND available = 1;', array($event->getId(), $userId));

        if ($a = self::fetchObject($res)) {
            $attendance = array('date' => $event->getDateFrom(), 'from' => $a->from, 'to' => $a->to, 'eventId' => $event->getId());
        }
        self::close($res);

        return $attendance;
    }

    public static function getAttendanceForAllTeachers($event)
    {
        if ($event == null) {
            return null;
        }

        $attendances = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT teacherId, firstName, lastName, MIN(dateFrom) AS `from`, MAX(dateTo) AS `to` 
                                                     FROM slot 
                                                     INNER JOIN user on slot.teacherId = user.id
                                                     WHERE eventId = ? AND available = 1 
                                                     GROUP BY teacherId
                                                     ORDER BY lastName;', array($event->getId()));

        while ($a = self::fetchObject($res)) {
            $attendances[] = array(
                'teacherId' => $a->teacherId,
                'firstName' => $a->firstName,
                'lastName' => $a->lastName,
                'date' => $event->getDateFrom(),
                'from' => $a->from,
                'to' => $a->to,
                'eventId' => $event->getId()
            );
        }
        self::close($res);

        return $attendances;
    }

    public static function calculateSlots($event, $asDummy = false)
    {
        $slots = array();
        $slotDuration = $event->getSlotTime();
        $startTime = $event->getDateFrom();
        $breakTimer = 0;
        $breakCounter = 1;
        $type = -1;
        while ($startTime < $event->getDateTo()) {
            $endTime = $startTime + ($slotDuration * 60);
            if ($event->getBreaks() == Event::NOBREAK) {
                $type = 1; //type = 1 normal; type = 2 break
            } else if ($event->getBreaks() == Event::FULLHOUR) {
                $type = (($breakTimer % 60 != 0) || ($breakTimer == 0)) ? 1 : 2;
            } else if ($event->getBreaks() == Event::HALFHOUR) {
                $type = (($breakTimer % 30 != 0) || ($breakTimer == 0)) ? 1 : 2;
            } else if ($event->getBreaks() == Event::BREAKTHREE) {
                $type = ($breakCounter % 3 != 0) ? 1 : 2;
            } else if ($event->getBreaks() == Event::BREAKFOUR) {
                $type = ($breakCounter % 4 != 0) ? 1 : 2;
            } else if ($event->getBreaks() == Event::BREAKFIVE) {
                $type = ($breakCounter % 5 != 0) ? 1 : 2;
            }
            if ($asDummy) {
                $slots[] = new Slot(null, null, null, null, $startTime, $endTime, $type, 1);
            } else {
                $slots[] = array('start' => $startTime, 'end' => $endTime, 'type' => $type);
            }
            $startTime = $endTime;
            $breakTimer += $slotDuration;
            $breakCounter++;
        }

        return $slots;
    }

    public static function getSlotsForTeacherId($eventId, $teacherId)
    {
        $slots = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, eventId, teacherId, studentId, dateFrom, dateTo, type, available FROM slot WHERE eventId = ? AND teacherId = ? AND available = 1;', array($eventId, $teacherId));

        while ($s = self::fetchObject($res)) {
            $slots[] = new Slot($s->id, $s->eventId, $s->teacherId, $s->studentId, $s->dateFrom, $s->dateTo, $s->type, $s->available);
        }
        self::close($res);
        return $slots;
    }

    public static function getBookedSlotsForStudent($eventId, $studentId)
    {
        $slots = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT s.id, s.eventId, s.teacherId, s.dateFrom, s.dateTo, u.firstName, u.lastName, u.title FROM slot AS s JOIN user AS u ON s.teacherId = u.id WHERE eventId = ? AND studentId = ?;', array($eventId, $studentId));

        while ($s = self::fetchObject($res)) {
            $slots[$s->dateFrom] = array('id' => $s->id, 'eventId' => $s->eventId, 'dateFrom' => $s->dateFrom, 'dateTo' => $s->dateTo, 'teacherId' => $s->teacherId, 'teacherName' => $s->title . ' ' . $s->firstName . ' ' . $s->lastName);
        }
        self::close($res);
        return $slots;
    }

    public static function getBookedSlotsForTeacher($eventId, $teacherId)
    {
        $slots = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT s.id, s.eventId, s.studentId, s.dateFrom, s.dateTo, u.firstName, u.lastName, u.class, u.role FROM slot AS s JOIN user AS u ON s.studentId = u.id WHERE eventId = ? AND teacherId = ?;', array($eventId, $teacherId));

        while ($s = self::fetchObject($res)) {
            $class = $s->role != 'student' ? ' (Lehrer)' : ' (' . $s->class . ')';
            $slots[$s->dateFrom] = array('id' => $s->id, 'studentId' => $s->studentId, 'eventId' => $s->eventId, 'dateFrom' => $s->dateFrom, 'dateTo' => $s->dateTo, 'studentName' => $s->firstName . ' ' . $s->lastName . $class);
        }
        self::close($res);
        return $slots;
    }

    public static function getNamesAndEmailAddressesForSlotId($slotId)
    {
        $con = self::getConnection();
        $res = self::query(
            $con,
            'SELECT s.id, s.dateFrom, s.dateTo, ' .
            'u.firstName as teacherFirstname, u.lastName as teacherLastname, u.email as teacherEmail, u.title as teacherTitle, ' .
            'u2.firstName as studentFirstname, u2.lastName as studentLastname, u2.email as studentEmail, u2.title as studentTitle ' .
            'FROM slot AS s ' .
            'JOIN user AS u ON s.teacherId = u.id ' .
            'JOIN user AS u2 ON s.studentId = u2.id ' .
            'WHERE s.id = ?;',
            array($slotId)
        );

        if ($s = self::fetchObject($res)) {
            $result = array(
                'dateFrom' => $s->dateFrom,
                'dateTo' => $s->dateTo,
                'teacherName' => trim($s->teacherTitle . ' ' . $s->teacherFirstname . ' ' . $s->teacherLastname),
                'studentName' => trim($s->studentTitle . ' ' . $s->studentFirstname . ' ' . $s->studentLastname),
                'teacherEmail' => $s->teacherEmail,
                'studentEmail' => $s->studentEmail
            );

        }
        self::close($res);
        return $result;
    }

    public static function setStudentToSlot($eventId, $slotId, $studentId)
    {
        $con = self::getConnection();
        $result = self::query($con, 'UPDATE slot SET studentId = ? WHERE id = ? AND eventId = ? AND type = 1 AND available = 1 AND studentId IS NULL;', array($studentId, $slotId, $eventId), true);

        return $result;
    }

    public static function deleteStudentFromSlot($eventId, $slotId)
    {
        $con = self::getConnection();
        $s = self::query($con, 'UPDATE slot SET studentId = NULL WHERE id = ? AND eventId = ?;', array($slotId, $eventId), true);

        return $s['success'];
    }
}
