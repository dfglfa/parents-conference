<?php

require_once('AbstractDAO.php');

class UserDAO extends AbstractDAO
{

    const MIN_PASSWORD_LENGTH = 8;

    public static function getUserForId($userId)
    {
        $user = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title FROM user WHERE id = ?;', array($userId));
        if ($u = self::fetchObject($res)) {
            $user = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $user;
    }

    public static function getUserForUserName($userName)
    {
        $user = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title  FROM user WHERE userName = ?;', array($userName));

        if ($u = self::fetchObject($res)) {
            $user = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $user;
    }

    public static function getUsersForRole($type, $limit = 0)
    {
        $users = array();
        $con = self::getConnection();
        $params = array($type);

        $orderPhrase = 'ORDER BY lastName';
        $query = sprintf('SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title, absent FROM user WHERE role = ? %s;', $orderPhrase);
        if ($limit > 0) {
            $query = 'SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title, absent FROM user WHERE role = ? ORDER BY lastName LIMIT 10';
        }
        $res = self::query($con, $query, $params);

        while ($u = self::fetchObject($res)) {
            $users[] = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $users;
    }


    public static function checkAccessData()
    {
        $con = self::getConnection();
        $res = self::query($con, 'SELECT * FROM accessdata;', array());

        $a = self::fetchObject($res);
        if ($a != null) {
            return true;
        } else {
            return false;
        }
    }

    public static function getStudentsForPasswordPrinting()
    {
        $users = array();
        $con = self::getConnection();
        $res = self::query(
            $con,
            'SELECT u.id, u.userName, a.password, u.firstName, u.lastName, u.email, u.class, u.role, u.title 
                    FROM user AS u JOIN accessdata AS a ON u.userName = a.userName WHERE role = ? ORDER BY u.class, u.lastName, u.firstName;',
            array('student')
        );

        while ($u = self::fetchObject($res)) {
            if (!isset($u->title)) {
                //continue;
            }
            $users[] = array(
                'student' => new User($u->id, $u->userName, "", $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title),
                'password' => $u->password
            );
        }

        self::close($res);
        return $users;
    }

    public static function deleteAccessData()
    {
        $con = self::getConnection();
        return self::query($con, 'DELETE FROM accessdata;', array(), true)['success'];
    }

    public static function getUsers()
    {
        $users = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title, absent FROM user ORDER BY LOWER(lastName), LOWER(firstName);', array());

        while ($u = self::fetchObject($res)) {
            $users[] = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $users;
    }

    public static function getPossibleSiblings($userId, $lastname)
    {
        $users = array();
        $con = self::getConnection();

        // Look for matching lastnames, ignoring case, whitespace and hyphens
        $res = self::query(
            $con,
            "SELECT id, userName, passwordHash, firstName, lastName, email, class, role, title
            FROM user 
            WHERE id != ? 
            AND LOWER(REPLACE(REPLACE(lastName, '-', ''), ' ', '')) = LOWER(REPLACE(REPLACE(?, '-', ''), ' ', ''));",
            [$userId, $lastname]
        );

        while ($u = self::fetchObject($res)) {
            $users[] = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $users;
    }

    public static function areUsersDirectlyConnected($userId1, $userId2)
    {
        $con = self::getConnection();

        $res = self::query(
            $con,
            "SELECT *
            FROM userconnection 
            WHERE userId1 = ? AND userId2 = ? OR userId1 = ? AND userId2 = ?",
            [$userId1, $userId2, $userId2, $userId1]
        );

        $rows = self::fetchObject($res);
        self::close($res);

        if ($rows) {
            return true;
        } else {
            return false;
        }
    }

    public static function connectUsers($userId1, $userId2)
    {
        $con = self::getConnection();
        $res = self::query($con, "
            INSERT INTO userconnection (userId1, userId2, activationToken, createdAt) 
            VALUES 
                (?, ?, '', UNIX_TIMESTAMP()),
                (?, ?, '', UNIX_TIMESTAMP())", [$userId1, $userId2, $userId2, $userId1]);

        self::close($res);
    }

    public static function bulkConnectUsers($userConnections)
    {
        $con = self::getConnection();
        foreach ($userConnections as $userName => $fullName) {
            $res = self::query(
                $con,
                "SELECT id
                FROM user
                WHERE userName = ?",
                [$userName]
            );

            $user1Rows = self::fetchObject($res);
            if ($user1Rows) {
                $userId1 = $user1Rows->id;
                error_log("Found user " . $userId1 . " for username " . $userName);
            }

            $nameParts = explode(",", $fullName);
            $res = self::query(
                $con,
                "SELECT id
                FROM user
                WHERE firstName = ? AND lastName = ?",
                [trim($nameParts[1]), trim($nameParts[0])]
            );

            $user2Rows = [];
            while ($row = self::fetchObject($res)) {
                $user2Rows[] = $row;
            }

            if (count($user2Rows) == 1) {
                $userId2 = $user2Rows[0]->id;
                try {
                    UserDAO::connectUsers($userId1, $userId2);
                } catch (Exception $exception) {
                    error_log("Could not connect " . $userId1 . " and " . $userId2 . ":" . $exception);
                }
            } else {
                error_log("Illegal number of matches for user fullname " . $fullName . " with name parts '" . $nameParts[0] . "' and '" . $nameParts[1] . "' (found " . count($user2Rows) . " matches) - skipping!");
                continue;
            }
        }
    }

    public static function disconnectUsers($userId1, $userId2)
    {
        $con = self::getConnection();
        $res = self::query($con, "
            DELETE FROM userconnection
            WHERE userId1 = ? AND userId2 = ?
            OR userId1 = ? AND userId2 = ?;
            ", [$userId1, $userId2, $userId2, $userId1]);

        self::close($res);
    }

    public static function getConnectedUsersForUserId($userId)
    {
        $users = array();
        $con = self::getConnection();

        // We need to build the transitive closure over the userconnection relation in order
        // to find all connections for the given user id.
        $res = self::query(
            $con,
            'WITH RECURSIVE reachable_ids AS (
                SELECT userId1 AS id
                FROM userconnection
                WHERE userId1 = ?
                
                UNION

                -- Recursive step: Find new IDs reachable from the already found IDs
                SELECT c.userId2
                FROM userconnection c
                INNER JOIN reachable_ids r ON c.userId1 = r.id
            )
                                
            SELECT u.id, userName, passwordHash, firstName, lastName, email, class, role, title, absent 
                FROM user u 
                JOIN reachable_ids r ON r.id = u.id
                WHERE u.id != ?',
            [$userId, $userId]
        );

        while ($u = self::fetchObject($res)) {
            $users[] = new User($u->id, $u->userName, $u->passwordHash, $u->firstName, $u->lastName, $u->email, $u->class, $u->role, $u->title);
        }
        self::close($res);
        return $users;
    }

    public static function register($userName, $password, $firstName, $lastName, $email, $class, $role)
    {
        $user = UserDAO::getUserForUserName($userName);
        if ($user != null) {
            return -1; // already registered
        } else if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return -2; // password too short
        }

        // register user in database
        $con = self::getConnection();
        $passwordHash = createPasswordHash($password);

        self::getConnection()->beginTransaction();
        self::query($con, 'INSERT INTO user (userName, passwordHash, firstName, lastName, email, class, role) VALUES (?, ?, ?, ?, ?, ?, ?);', array($userName, $passwordHash, $firstName, $lastName, $email, $class, $role));
        $userId = self::lastInsertId($con);

        if ($role == 'teacher') {
            $activeEvent = EventDAO::getActiveEvent();
            if ($activeEvent != null) {
                $teacher = UserDAO::getUserForId($userId);
                SlotDAO::createSlotsForEvent($activeEvent->getId(), array($teacher));
            }
        } else {
            if (UserDAO::checkAccessData()) {
                $accessData = array();
                $accessData[] = array($userName, $password);
                UserDAO::bulkInsertAccessData($accessData, false);
            }
        }

        self::getConnection()->commit();

        return $userId;
    }

    public static function bulkInsertUsers($users, $rooms = null)
    {
        $con = self::getConnection();
        $userSth = $con->prepare('INSERT INTO user (userName, passwordHash, firstName, lastName, email, class, role, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?);');
        $roomSth = self::getConnection()->prepare('INSERT IGNORE INTO room (roomNumber, name, teacherId) VALUES (?, ?, ?);');
        $isTeacherInsert = count($rooms) > 0;

        foreach ($users as $user) {
            for ($i = 0; $i < 8; $i++) {
                $userSth->bindValue($i + 1, $user[$i]);
            }
            $userSth->execute();

            if ($isTeacherInsert) {
                $userId = self::lastInsertId($con);
                $userName = $user[0];
                if (array_key_exists($userName, $rooms)) {
                    $room = $rooms[$userName];
                    $roomSth->bindValue(1, $room[0]);
                    $roomSth->bindValue(2, $room[1]);
                    $roomSth->bindValue(3, $userId);
                    $roomSth->execute();
                }
            }
        }
    }

    public static function bulkInsertAccessData($accessData, $delete = true)
    {
        $con = self::getConnection();
        if ($delete) {
            self::query($con, 'DELETE FROM accessdata;', array());
        }

        $sth = $con->prepare('INSERT INTO accessdata (userName, password) VALUES (?, ?);');

        foreach ($accessData as $access) {
            foreach ($access as $attr => $value) {
                $sth->bindValue($attr + 1, $value);
            }
            $sth->execute();
        }
    }

    public static function update($userId, $userName, $password, $firstName, $lastName, $email, $class, $type)
    {
        $con = self::getConnection();
        $success = true;

        if ($userId == 1 && $type != 'admin') {
            return false;
        }

        if ($password != '') {
            if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
                return false;
            }

            $passwordHash = createPasswordHash($password);
            $query = 'UPDATE user SET passwordHash = ?, userName = ?, firstName = ?, lastName = ?, email = ?, class = ?, role = ? WHERE id = ?';
            $params = array($passwordHash, $userName, $firstName, $lastName, $email, $class, $type, $userId);
            $updateUserResult = self::query($con, $query, $params, true)['success'];

            $accessDataQuery = 'UPDATE accessdata SET password = ? WHERE userName = ?;';
            $accessDataParams = array($password, $userName);
            $accessDataResult = self::query($con, $accessDataQuery, $accessDataParams, true)['success'];

            $success = $updateUserResult && $accessDataResult;
        } else {
            $oldUserName = "";
            $oldUserQuery = self::query($con, 'SELECT userName FROM user WHERE id = ?;', array($userId));
            if ($u = self::fetchObject($oldUserQuery)) {
                $oldUserName = $u->userName;
            }

            if ($oldUserName != $userName) {
                return false;
            }

            $query = 'UPDATE user SET firstName = ?, lastName = ?, email = ?, class = ?, role = ? WHERE id = ?';
            $params = array($firstName, $lastName, $email, $class, $type, $userId);

            $success = self::query($con, $query, $params, true)['success'];
        }

        if ($userId == AuthenticationManager::getAuthenticatedUser()->getId()) {
            $_SESSION['user'] = UserDAO::getUserForId($userId);
        }

        return $success;

        // userName and password have to be set!!!


        /*
        // with userName as Salt and possibility to only change the userName
        $hashQueryPart = '';


        $updatePassword = ($userName != null) || ($password != null);
        if ($updatePassword) {
            $passwordHash = createPasswordHash($password);
            $hashQueryPart = ' passwordHash = ?,';
            $params = array_merge(array($passwordHash), $params);
        }

        $query = sprintf('UPDATE user SET%s userName = ?, firstName = ?, lastName = ?, class = ?, role = ? WHERE id = ?', $hashQueryPart);

        return self::query($con, $query, $params, true);
        */
    }

    public static function updateAbsent($userId, $absent)
    {
        $con = self::getConnection();
        $query = 'UPDATE user SET absent = ? WHERE Id = ?;';
        $params = array($absent, $userId);
        $result = self::query($con, $query, $params, true)['success'];
        return $result;
    }

    public static function deleteUsersByRole($role)
    {
        $con = self::getConnection();

        return self::query($con, 'DELETE FROM user WHERE role = ?', array($role), true)['success'];
    }

    public static function deleteUserById($userId)
    {
        $con = self::getConnection();

        if ($userId == 1) {
            return false;
        }

        return self::query($con, 'DELETE FROM user WHERE id = ?', array($userId), true)['success'];
    }

    public static function getAllConnections()
    {
        $con = self::getConnection();
        return self::query(
            $con,
            '
            SELECT uc.userId1, uc.userId2, u1.firstName as firstName1, u1.lastName lastName1, u2.firstName as firstName2, u2.lastName as lastName2
            from userconnection uc
            inner join user u1 on u1.id = uc.userId1
            inner join user u2 on u2.id = uc.userId2',
            array()
        );
    }

    public static function deleteAllConnections()
    {
        $con = self::getConnection();
        return self::query($con, 'DELETE FROM userconnection', array(), true)['success'];
    }
}
