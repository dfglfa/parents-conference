<?php
require_once('code/dao/EventDAO.php');
require_once('code/ViewController.php');
require_once('code/AuthenticationManager.php');
AuthenticationManager::checkPrivilege('teacher');

include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/teacher.js'></script>

<p id='pageName' hidden>Teacher</p>

<div class='container'>

    <?php
    printAlertForTemplate("teacherOverview");
    ?>

    <h1>Meine Übersicht</h1>

    <div class='panel-group' id='accordion'>


        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#collapse2'>
                        Meine Anwesenheit
                    </a>
                </h4>
            </div>
            <div id='collapse2' class='panel-collapse collapse'>
                <div class='panel-body'>

                    <h4>
                        Aktuelle Anwesenheit
                    </h4>
                    <p id='attendance'>
                        <?php
                        $viewController = ViewController::getInstance();
                        $attendance = $viewController->action_attendance();
                        $event = EventDAO::getActiveEvent();
                        $canChangeAttendance = $event != null && time() < $event->getStartPostDate();
                        ?>
                    </p>

                    <?php if ($attendance != null && $canChangeAttendance): ?>
                        <h4>
                            Anwesenheit ändern
                        </h4>
                        <form id='changeAttendanceForm'>
                            <input type='hidden' name='userId' value='<?php echo (escape($user->getId())) ?>'>
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
                                Anwesenheit ändern
                            </button>

                        </form>

                        <div class='message' id='message'></div>
                    <?php else: ?>
                        <div class="text-danger">Du kannst Deine Anwesenheit nicht mehr ändern, da Buchungen bereits möglich
                            sind.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#collapse1'>
                        Mein Terminplan
                    </a>
                </h4>
            </div>
            <div id='collapse1' class='panel-collapse collapse in'>
                <div class='panel-body'>
                    <button class="btn btn-primary" onclick="window.print()">
                        <span class='glyphicon glyphicon-print'></span>&nbsp;&nbsp;Zeitplan ausdrucken
                    </button>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="showempty">
                            Freie Zeiträume ausblenden
                        </label>
                    </div>

                    <div id='timeTable' class="section-to-print"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'inc/footer.php'; ?>