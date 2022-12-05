<?php
if (isset($_POST['epaper-startdate']) ) {
    $_SESSION['epaper-startdate'] = $_POST['epaper-startdate'];
    echo json_encode(array('success' => 1));
} else {
    echo json_encode(array('success' => 0));
}