<nav class='navbar navbar-default navbar-fixed-top'>
    <div class='container'>
        <div class='navbar-header'>
            <button type='button' class='navbar-toggle collapsed' data-toggle='collapse' data-target='#navbar'
                aria-expanded='false' aria-controls='navbar'>
                <span class='sr-only'>Toggle navigation</span>
                <span class='icon-bar'></span>
                <span class='icon-bar'></span>
                <span class='icon-bar'></span>
            </button>
            <a class='navbar-brand custom-nav' href='redirectUser.php'>
                <img id="navLogo" src="<?php echo file_exists("public/logo.png") ? "public/logo.png" : "" ?>"
                    style="display:inline-block; height: 30px; margin: -5px 5px 0 0">
                <?php if (EventDAO::getActiveEvent() != null):
                    echo EventDAO::getActiveEvent()->getName(); ?>
                <?php else: ?>
                    Elternsprechtag
                <?php endif ?>
            </a>
        </div>
        <div id='navbar' class='navbar-collapse collapse'>

            <ul class='nav navbar-nav'>
                <?php if ($user->getRole() == 'student'): ?>
                    <li id='navTabHome'><a href='home.php'>Meine Termine</a></li>
                    <li id='navTabBook'><a href='book.php'>Termine buchen</a></li>
                    <?php if (file_exists("public/map.png")): ?>
                        <li id='navTabMap'><a href='map.php'>Lageplan</a></li>
                    <?php endif ?>
                <?php endif ?>
                <?php if ($user->getRole() == 'teacher') { ?>
                    <li id='navTabTeacher'><a href='teacher.php'>Übersicht</a></li>
                <?php } ?>
                <?php if ($user->getRole() == 'admin') { ?>
                    <li id='navTabAdmin'><a href='admin.php'>Administration</a></li>
                <?php } ?>
            </ul>

            <ul class='nav navbar-nav navbar-right'>

                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true'
                        aria-expanded='false'>
                        <span class='glyphicon glyphicon-user'></span>
                        &nbsp;eingeloggt als:
                        <?php echo escape($user->getUserName()); ?>
                        &nbsp;<span class='caret'></span>
                    </a>
                    <ul class='dropdown-menu'>
                        <li><a href='profile.php'><span class='glyphicon glyphicon-user'></span> Benutzerprofil</a></li>
                        <li><a href='code/actions/logout.php'><span class='glyphicon glyphicon-log-out'></span>
                                Ausloggen</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>