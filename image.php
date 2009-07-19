<?php
include_once("3dglobe.php");
if($flat) $img = paint_flat(time_utc(), $cmap);
else      $img = paint_globe($lat, $lon, time_utc(), $res, $cmap);
header('Content-type: image/png');
imagepng($img);
imagedestroy($img);
?>
