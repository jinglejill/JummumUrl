<?php
    $result = array();
    $result[] = array("version" => "1.5.5");
    $lookup = array("resultCount" => 1, "results" => $result);
    
    echo json_encode($lookup);
?>

