<?php
require_once('code/dao/MessageDAO.php');
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


    <?php endif; ?>

    <?php
}

?>