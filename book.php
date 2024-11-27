<?php
require_once('code/dao/EventDAO.php');
include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/mySlots.js'></script>
<script type='text/javascript' src='js/book.js'></script>

<p id='pageName' hidden>Book</p>

<div class='container'>
    <div id='tabs-1'>
        <h1>Termine buchen</h1>
        <?php include_once 'inc/notifications.php'; ?>
    </div>
</div>

<?php
$activeEvent = EventDAO::getActiveEvent();
?>

<div class='container'>
    <div>
        <?php if ($activeEvent != null): ?>
            <?php if ($activeEvent->getStartPostDate() > time()):
                $timestamp = $activeEvent->getStartPostDate();
                $date = new DateTime("@$timestamp");
                $berlinTimezone = new DateTimeZone('Europe/Berlin');
                $date->setTimezone($berlinTimezone);
                $formattedDate = $date->format('d.m.Y h:i') . " Uhr";
                ?>
                <h3>Buchungen sind erst ab dem <?php echo $formattedDate ?>
                    möglich </h3>
            <?php elseif ($activeEvent->getFinalPostDate() < time()): ?>
                <h3>Buchungen sind nicht mehr möglich!</h3>
            <?php else: ?>
                <div id="quotaInformation"></div>
                <div class="alert alert-info">
                    Bitte beachten Sie, dass zwischen zwei Terminen längere Fußwege anfallen können.
                    Daher sollte vermieden werden, zwei aufeinander folgende Termine zu buchen.
                </div>
                <form id='chooseTeacherForm'>
                    <div class='form-group'>
                        <label for='selectTeacher'>Lehrer / Lehrerin</label>
                        <select class='form-control' id='selectTeacher' name='teacher'>
                            <?php echo (getTeacherOptions()); ?>
                        </select>
                    </div>
                </form>
                <div id='timeTable'></div>
            <?php endif; ?>
        <?php else: ?>
            <h3>Es gibt momentan keinen Elternsprechtag!</h3>
        <?php endif; ?>
    </div>
</div>


<?php include_once 'inc/footer.php'; ?>