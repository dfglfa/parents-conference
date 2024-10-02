<?php
require_once('code/AuthenticationManager.php');
AuthenticationManager::checkPrivilege('admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwörter</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
        }

        /* Style for each section */
        .section {
            display: flex;
            justify-content: center;
            align-items: left;
            flex-direction: column;
            padding: 10px 20px;
            border-bottom: 1px dashed gray;
        }

        h4 {
            margin: 0;
            padding: 0;
            padding-bottom: 10px;
        }

        td {
            padding-right: 20px;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }

            table {
                border: 0;
            }
        }
    </style>
</head>

<body>
    <?php
    $role = $_GET["role"];

    if ($role != "student" && $role != "teacher") {
        echo "Unknown role " . $role;
        return;
    }

    $users = UserDAO::getUsersAccessDataForRole($role);
    $activeEvent = EventDAO::getActiveEvent();
    $userIndex = 0;
    $currentClass = null;

    // Insert a page break after n users
    $BREAK_AFTER_N = 7;
    ?>

    <?php foreach ($users as $entry):
        $user = $entry['user'];
        ?>

        <?php if ($role == "student" && $currentClass != $user->getClass()):
            ?>
            <?php if ($userIndex % $BREAK_AFTER_N != 0):
                $userIndex = 0;
                ?>
                <div class="page-break"></div>
            <?php endif; ?>

            <div>
                <h2>Klasse <?php echo $user->getClass() ?></h2>
            </div>
        <?php endif; ?>

        <?php
        $currentClass = $user->getClass();
        $userIndex += 1;
        ?>

        <div class="section">
            <div>
                <h4> Zugangsdaten für den Elternsprechtag
                    <?php echo $activeEvent != null ? "am " . toDate($activeEvent->getDateFrom(), 'd.m.Y') : "" ?>
                </h4>
            </div>
            <div>
                <table>
                    <tr>
                        <td><strong>Klasse</strong></td>
                        <td><?php echo $user->getClass() ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $role == "teacher" ? "Lehrkraft" : "Name" ?></strong></td>
                        <td><?php echo $user->getLastName() . ", " . $user->getFirstName() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login</strong></td>
                        <td><?php echo $user->getUserName() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Passwort</strong></td>
                        <td><?php echo $entry["password"] ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($userIndex % $BREAK_AFTER_N == 0): ?>
            <div class="page-break"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</body>

</html>