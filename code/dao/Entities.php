<?php

abstract class Entity
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}

class User extends Entity
{
    private $userName;
    private $passwordHash;
    private $firstName;
    private $lastName;
    private $email;
    private $class;
    private $role;
    private $title;
    private $absent;

    public function __construct($id, $userName, $passwordHash, $firstName, $lastName, $email, $class, $role, $title, $absent = 0)
    {
        parent::__construct($id);
        $this->userName = $userName;
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->class = $class;
        $this->role = $role;
        $this->title = $title;
        $this->absent = $absent;
    }


    public function getUserName()
    {
        return $this->userName;
    }

    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function isAbsent()
    {
        return $this->absent;
    }

    public function __toString()
    {
        return json_encode(
            array(
                'id' => $this->getId(),
                'userName' => $this->userName,
                'passwordHash' => $this->passwordHash,
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'class' => $this->class,
                'role' => $this->role,
                'title' => $this->title,
                'absent' => $this->absent
            )
        );
    }
}

class Event extends Entity
{
    private $name;
    private $dateFrom;
    private $dateTo;
    private $slotTime;
    private $isActive;
    private $startPostDate;
    private $finalPostDate;
    private $videoLink;
    private $breaks;
    private $throttleDays;
    private $throttleQuota;
    
    public function __construct($id, $name, $dateFrom, $dateTo, $slotTime, $isActive, $startPostDate, $finalPostDate, $videoLink, $breaks, $throttleDays, $throttleQuota)
    {
        parent::__construct($id);
        $this->name = $name;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->slotTime = $slotTime;
        $this->isActive = $isActive;
        $this->finalPostDate = $finalPostDate;
        $this->startPostDate = $startPostDate;
        $this->videoLink = $videoLink;
        $this->breaks = $breaks;
        $this->throttleDays = $throttleDays;
        $this->throttleQuota = $throttleQuota;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    public function getDateTo()
    {
        return $this->dateTo;
    }

    public function getSlotTime()
    {
        return $this->slotTime;
    }

    public function isActive()
    {
        return $this->isActive;
    }
    public function getStartPostDate()
    {
        return $this->startPostDate;
    }

    public function getFinalPostDate()
    {
        return $this->finalPostDate;
    }
    public function getVideoLink()
    {
        if (strlen($this->videoLink) > 10) {

            $lastchar = $this->videoLink[-1];
            if (strcmp($lastchar, "/") === 0) {
                return $this->videoLink;
            } else {
                return $this->videoLink . "/";
            }
        } else {
            return null;
        }
    }
    public function getBreaks()
    {
        return $this->breaks;
    }
    public function getThrottleDays()
    {
        return $this->throttleDays;
    }

    public function getThrottleQuota()
    {
        return $this->throttleQuota;
    }

    public function getMaxIndividualBreaks() {
      return $this->breaks > 0 ? $this->breaks : 0;
    }
}

class Slot extends Entity
{
    private $eventId;
    private $teacherId;
    private $studentId;
    private $dateFrom;
    private $dateTo;
    private $type;
    private $available;

    public function __construct($id, $eventId, $teacherId, $studentId, $dateFrom, $dateTo, $type, $available)
    {
        parent::__construct($id);
        $this->eventId = $eventId;
        $this->teacherId = $teacherId;
        $this->studentId = $studentId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->type = $type;
        $this->available = $available;
    }

    public function getEventId()
    {
        return $this->eventId;
    }

    public function getTeacherId()
    {
        return $this->teacherId;
    }

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    public function getDateTo()
    {
        return $this->dateTo;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getAvailable()
    {
        return $this->available;
    }
}

class Log extends Entity
{
    private $userId;
    private $action;
    private $info;
    private $date;

    public function __construct($id, $userId, $action, $info, $date)
    {
        parent::__construct($id);
        $this->userId = $userId;
        $this->action = $action;
        $this->info = $info;
        $this->date = $date;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getDate()
    {
        return $this->date;
    }
}

class Room extends Entity
{
    private $roomNumber;
    private $name;
    private $teacherId;

    public function __construct($id, $roomNumber, $name, $teacherId)
    {
        parent::__construct($id);
        $this->roomNumber = $roomNumber;
        $this->name = $name;
        $this->teacherId = $teacherId;
    }

    public function getRoomNumber()
    {
        return $this->roomNumber;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTeacherId()
    {
        return $this->teacherId;
    }
}

class Message extends Entity
{
    private $senderId;
    private $receiverId;
    private $content;

    private $createdAt;

    public function __construct($id, $senderId, $receiverId, $content, $createdAt)
    {
        parent::__construct($id);
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->content = $content;
        $this->createdAt = $createdAt;
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function getReceiverId()
    {
        return $this->receiverId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
