</div>
</div>
</div>
</div>
</div>
<script src='libs/bootstrap/js/bootstrap.min.js'></script>

<div id="confirmationDialog" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="confirmationTitle"></h4>
            </div>
            <div class="modal-body" id="confirmationContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-success" data-dismiss="modal" id="confirmationButton">OK</button>
            </div>
        </div>

    </div>
</div>


</body>

</html>

<script>
    $(document).ready(function () {
        $.getScript('js/util.js', function () {
            setTab();
        });
    });
</script>