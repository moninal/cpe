<?php 
    if(file_exists("../XML/20531588119-03-B020-1.xml")) {
        echo "existe";

        rename("../XML/20531588119-03-B020-1.xml", "../XML/20531588119-03-B020-1_".date("dmY")."_".date("His").".xml");
    }   

?>