<?php
        session_start();
        $filename = $_POST["archiveName"];

        $app_path = __DIR__."/sessions/";
        $personnal_path = $_SERVER['REMOTE_ADDR'] . "-" . session_id();
        $filePath = $app_path . $personnal_path . '/' . $filename;

        $filePath = str_replace('..', '', $filePath);

        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=\"{$filename}\"");
        readfile($filePath);

        exit();
?>
