<?php
    if(!file_exists("XML/")) {
        mkdir("XML/", 0777);
    }
    if(!file_exists("PDF/")) {
        mkdir("PDF/", 0777);
    }
    if(!file_exists("CDR/")) {
        mkdir("CDR/", 0777);
    }
    if(!file_exists("PNG/")) {
        mkdir("PNG/", 0777);
    }
    if(!file_exists("QR/")) {
        mkdir("QR/", 0777);
    }

    echo "OK";
?>