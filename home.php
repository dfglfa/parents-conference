<?php
include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/mySlots.js'></script>

<p id='pageName' hidden>Home</p>
<div class='container'>
    <div id='tabs-1'>
        <h1>Meine Termine</h1>
        <?php include_once 'inc/notifications.php'; ?>
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