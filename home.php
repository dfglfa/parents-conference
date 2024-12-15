<?php
include_once 'inc/header.php';
include_once 'code/Util.php';
?>

<script type='text/javascript' src='js/mySlots.js'></script>

<p id='pageName' hidden>Home</p>
<div class='container'>
    <div id='tabs-1'>
        <?php
        printAlertForTemplate("studentOverview");
        ?>
        <h1>Meine Termine</h1>
        <div id="siblingsHint"></div>
        <?php include_once 'inc/notifications.php'; ?>
    </div>
</div>

<div class='container'>


    <div>
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

<?php include_once 'inc/footer.php'; ?>