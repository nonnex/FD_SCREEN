<?php
include("../config.php");

if (isset($_POST["id"])) {
    $db->deleteById(CALENDAR_TABLE,$_POST['id']);
}
