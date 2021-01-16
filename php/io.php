<?php

function echo_header_body_footer($prefixFile, $bodyFile, $postfixFile) { 
    echo file_get_contents(filebase . $prefixFile);
    echo file_get_contents(filebase . $bodyFile);
    echo file_get_contents(filebase . $postfixFile);
}
?>