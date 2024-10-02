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
    $users = UserDAO::getStudentsForPasswordPrinting();
    $activeEvent = EventDAO::getActiveEvent();
    $userIndex = 0;
    $currentClass = null;
    ?>

    <?php foreach ($users as $entry):
        $student = $entry['student'];
        ?>

        <?php if ($currentClass != $student->getClass() && ($userIndex % 7 != 0)):
            $userIndex = 0;
            ?>
            <div class="page-break"></div>
        <?php endif; ?>

        <?php
        $currentClass = $student->getClass();
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
                        <td><?php echo $student->getClass() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td><?php echo $student->getLastName() . ", " . $student->getFirstName() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login</strong></td>
                        <td><?php echo $student->getUserName() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Passwort</strong></td>
                        <td><?php echo $entry["password"] ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($userIndex % 7 == 0): ?>
            <div class="page-break"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</body>

</html>