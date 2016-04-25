<?php
    $maps = file_get_contents("http://83.145.232.209:10001/?type=vehicles&lng1=23&lat1=60&lng2=26&lat2=61&online=1");
    echo $maps;
?>