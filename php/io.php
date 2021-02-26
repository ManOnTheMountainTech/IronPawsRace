<?php

function echo_header_body_footer($prefixFile, $bodyFile, $postfixFile) { 
    echo file_get_contents(filebase . EN_HTML . $prefixFile);
    echo file_get_contents(filebase . EN_HTML . $bodyFile);
    echo file_get_contents(filebase . EN_HTML . $postfixFile);
}
?>