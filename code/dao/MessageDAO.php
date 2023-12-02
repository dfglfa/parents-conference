<?php

require_once('AbstractDAO.php');

class MessageDAO extends AbstractDAO
{

    public static function initDB()
    {
        $con = self::getConnection();
        self::query($con, "
        CREATE TABLE `message` (
            `id` int(11) NOT NULL,
            `senderId` int(11) NOT NULL,
            `receiverId` int(11) NOT NULL,
            `content` varchar(2000) COLLATE utf8_bin NOT NULL,
            `createdAt` int(11) NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
        ");
    }

    public static function getAllMessages()
    {
        $messages = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, senderId, receiverId, content, createdAt FROM message;');
        while ($m = self::fetchObject($res)) {
            $messages[] = new Message($m->id, $m->senderId, $m->receiverId, $m->content, $m->createdAt);
        }
        self::close($res);
        return $messages;
    }

    public static function getMessagesForUser($userId)
    {
        $messages = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, senderId, receiverId, content, createdAt FROM message WHERE receiverId = ?;', array($userId));
        while ($m = self::fetchObject($res)) {
            $messages[] = new Message($m->id, $m->senderId, $m->receiverId, $m->content, $m->createdAt);
        }
        self::close($res);
        return $messages;
    }

    public static function createMessage($senderId, $receiverId, $content)
    {
        $con = self::getConnection();
        $res = self::query($con, 'INSERT INTO message (senderId, receiverId, content, createdAt) values (?, ?, ?, now())', array($senderId, $receiverId, $content), true)["success"];
        return $res ? "OK" : "NOPE: '" . $res . "'";
    }

    public static function deleteMessageForReceiverId($messageId, $receiverId)
    {
        $con = self::getConnection();
        return self::query($con, 'DELETE FROM message WHERE id = ? AND receiverId = ?;', array($messageId, $receiverId));
    }
}
