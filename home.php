<?php
require_once('code/dao/MessageDAO.php');
include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/mySlots.js'></script>

<p id='pageName' hidden>Home</p>

<div class='container'>
    <div id='tabs-1'>
        <h1>Meine gebuchten Termine</h1>
        <h3>Hier können Sie Ihre gebuchten Termine einsehen und löschen!<br><br></h3>

        <?php
        $messages = MessageDAO::getMessagesForUser($_SESSION['userId']);
        foreach ($messages as $msg) {
            ?>
            <?php if (!empty($msg->getContent())): ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="messageDismissal close" data-dismiss="alert" aria-label="Close"><span
                            id="dismiss_<?php echo $msg->getId() ?>" data-messageid="<?php echo $msg->getId() ?>"
                            data-receiverid="<?php echo $_SESSION['userId'] ?>" aria-hidden="true">&times;</span></button>
                    <?php echo $msg->getContent(); ?>
                </div>

            </div>
        <?php endif; ?>
        <?php
        }

        ?>
</div>
</div>

<div class='container'>
    <div>
        <form id='chooseMySlotsForm'>
            <div class='form-group'>
                <label for='selectType'>Darstellungstyp</label>
                <select class='form-control' id='selectType' name='type'>
                    <option value='1' selected>Kompakt</option>
                    <option value='0'>Vollständig</option>
                </select>
            </div>
        </form>

        <button class="btn btn-primary" onclick="window.print()">
            <span class='glyphicon glyphicon-print'></span>&nbsp;&nbsp;Zeitplan ausdrucken
        </button>

        <div id='timeTable' class="section-to-print"></div>
    </div>
</div>

<?php include_once 'inc/footer.php'; ?>