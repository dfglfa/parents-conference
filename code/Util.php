<?php

require_once('dao/EventDAO.php');

function escape($string)
{
    return nl2br(htmlentities($string ?? ""));
}

class SessionContext
{
    private static $isCreated = false;

    public static function create()
    {
        if (!self::$isCreated) {
            self::$isCreated = session_start();
        }
        return self::$isCreated;
    }
}

function redirect($page = null)
{
    if ($page == null) {
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : $_SERVER['REQUEST_URI'];
    }
    header("Location: $page");
}

function action($action, $params = null)
{
    $res = 'controller.php?action=' . rawurlencode($action);
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : $_SERVER['REQUEST_URI'];
    $res .= '&page=' . rawurlencode($page);
    if (is_array($params)) {
        foreach ($params as $name => $value) {
            $res .= '&' . rawurlencode($name) . '=' . rawurlencode($value);
        }
    }
    echo $res;
}

function createPasswordHash($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}


function getDateOptions($attendance, $dateFrom = true)
{
    $activeEvent = EventDAO::getActiveEvent();

    $time = $activeEvent->getDateFrom();
    $endTime = $activeEvent->getDateTo();

    $options = '';
    while ($time <= $endTime) {
        $halfHour = 60 * 60 / 2;
        $selected = '';
        if ($dateFrom && $time == $attendance['from']) {
            $selected = ' selected';
        } else if (!$dateFrom && $time == $attendance['to']) {
            $selected = ' selected';
        }
        $options .= sprintf('<option value="%s"%s>%s</option>', $time, $selected, date('H:i', $time));
        $time += $halfHour;
    }

    return $options;
}

function getTeacherOptions()
{
    $teachers = UserDAO::getUsersForRole('teacher');

    $options = '<option value="-1">Lehrkraft auswählen</option>';
    foreach ($teachers as $teacher) {
        $options .= sprintf('<option value="%s" %s>%s</option>', $teacher->getId(), ($teacher->isAbsent() == 1 ? 'disabled' : ''), $teacher->getLastName() . ' ' . $teacher->getTitle() . " " . $teacher->getFirstName() . ' ' . ($teacher->isAbsent() == 1 ? ' - abwesend' : ''));
    }

    return $options;
}

function toDate($timestamp, $format)
{
    return date($format, $timestamp);
}

function getActionString($actionId)
{
    switch ($actionId) {
        case 1:
            return 'eingeloggt';
        case 2:
            return 'ausgeloggt';
        case 3:
            return 'Termin gebucht';
        case 4:
            return 'Termin gelöscht';
        case 5:
            return 'Anwesenheit geändert';
        default:
            return 'Unbekannte Aktion';
    }
}

function getActiveConferenceText()
{
    $activeEvent = EventDAO::getActiveEvent();
    if ($activeEvent != null) {
        return "Elternsprechtag am " . toDate($activeEvent->getDateFrom(), 'd.m.Y');
    } else {
        return "Es gibt momentan keinen aktiven Elternsprechtag!";
    }
}

function optionalBreak()
{
    return '<span class="no-print"><br></span><span class="only-print"> - </span>';
    //return '<span class="only-print"> - </span>';
}

function getMaximumNumberOfBookableSlotsUntilCurrentTime()
{
    $activeEvent = EventDAO::getActiveEvent();
    if ($activeEvent == null || $activeEvent->getThrottleQuota() == 0) {
        return -1;
    }

    if (getThrottleEndTime() < time()) {
        return -1;
    }

    $startTime = $activeEvent->getStartPostDate();
    $fullDaysPassed = floor((time() - $startTime) / 86400);
    return $activeEvent->getThrottleQuota() * (1 + $fullDaysPassed);
}

function getThrottleEndTime()
{
    $activeEvent = EventDAO::getActiveEvent();
    if ($activeEvent == null || $activeEvent->getThrottleQuota() == 0) {
        return null;
    }

    return $activeEvent->getStartPostDate() + $activeEvent->getThrottleDays() * 86400;
}

function getDataForMailTemplate($templateId)
{
    $template_filename = "uploads/" . $templateId . ".json";
    if (!file_exists($template_filename)) {
        $template_filename = "templates/email/" . $templateId . ".json";

        if (!file_exists($template_filename)) {
            echo "Cannot resolve file for template ID " . $templateId;
            return null;
        }
    }

    $file_content = file_get_contents($template_filename);
    return json_decode($file_content, true);
}