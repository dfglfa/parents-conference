<?php

require_once("AbstractDAO.php");
require_once("Entities.php");

class Invitation extends AbstractDAO
{
  public static function getInvitationsToStudent(string $studentId)
  {
    $invitations = array();
    $con = self::getConnection();
    $res = self::query($con, 'SELECT id, teacherId, studentId, createdAt FROM invitation where studentId = ?;', [$studentId]);
    while ($i = self::fetchObject($res)) {
      $invitations[] = new Invitation($i->id, $i->senderId, $i->receiverId, $i->createdAt);
    }
    self::close($res);
    return $invitations;
  }

  public static function getInvitationsFromTeacher(string $teacherId) {
    $invitations = array();
    $con = self::getConnection();
    $res = self::query($con, 'SELECT id, teacherId, studentId, createdAt FROM invitation where teacherId = ?;', [$teacherId]);
    while ($i = self::fetchObject($res)) {
      $invitations[] = new Invitation($i->id, $i->senderId, $i->receiverId, $i->createdAt);
    }
    self::close($res);
    return $invitations;
  }

  public static function createInvitationToStudent(string $teacherId, string $studentId) {
    $con = self::getConnection();
    $res = self::query($con, 'INSERT INTO invitation (teacherId, studentId, createdAt) VALUES (?, ?, unix_timestamp())', [$teacherId, $studentId])["success"];
    self::close($res);
    return $res ? "OK" : "ERROR";
  }

  public static function deleteInvitationToParent(string $teacherId, string $studentId) {
    $con = self::getConnection();
    $res = self::query($con, 'DELETE FROM invitation WHERE teacherId = ? AND studentId = ?', [$teacherId, $studentId])["success"];
    self::close($res);
    return $res ? "OK" : "ERROR";
  }
}
