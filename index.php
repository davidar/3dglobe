<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<title>3D Globe</title>
<!--
Copyright (C) 2007-2009  David Roberts <d@vidr.cc>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
02110-1301, USA.
-->
<?php include_once("3dglobe.php"); ?>
<?php $qstr = htmlspecialchars($_SERVER['QUERY_STRING']); ?>
<style type="text/css">
body {
    background-color: black;
    color: white;
    text-align: center;
}

table {
    margin: 0 auto;
}

<?php if($flat): ?>
#map {
    width: 100%;
    height: 180px;
    background-image: url('image.php?<?php echo $qstr; ?>');
}
<?php else: ?>
#map {
    height: <?php echo $res; ?>px;
}

input.image {
    cursor: crosshair;
}
<?php endif; ?>
</style>
</head>
<body>
<form action="" method="get">
    <?php if($flat): ?>
    <div id="map"></div>
    <?php else: ?>
    <div id="map"><input type="image" class="image" name="map" src="image.php?<?php echo $qstr; ?>" alt="Loading..." /></div>
    <p>Either click on image above or manually enter coordinates below to change view</p>
    <p><a href="?t=s">View from sun</a></p>
    <?php endif; ?>
    
    <table>
        <tr><th>Projection:</th><td>
            <select name="p">
                <option value="globe" <?php if(!$flat): ?>selected="selected"<?php endif; ?>>Globe</option>
                <option value="flat"  <?php if($flat):  ?>selected="selected"<?php endif; ?>>Flat</option>
            </select>
        </td></tr>
        <?php if(!$flat): ?>
        <tr><th>Latitude:</th> <td><input type="text" name="lat" value="<?php echo $lat; ?>" /></td></tr>
        <tr><th>Longitude:</th><td><input type="text" name="lon" value="<?php echo $lon; ?>" /></td></tr>
        <?php endif; ?>
        <tr><th>Map:</th><td>
            <input type="radio" name="cmap" value="shade" <?php if($cmap == "shade"): ?>checked="checked"<?php endif; ?> /> Shaded
            <input type="radio" name="cmap" value="day"   <?php if($cmap == "day"):   ?>checked="checked"<?php endif; ?> /> Day
            <input type="radio" name="cmap" value="night" <?php if($cmap == "night"): ?>checked="checked"<?php endif; ?> /> Night
        </td></tr>
        <tr><td></td><td><input type="submit" /></td></tr>
    </table>
</form>
<hr />
<p><a href="http://earthobservatory.nasa.gov/Features/BlueMarble/images_bmng/8km/world.topo.bathy.200407.3x5400x2700.jpg">Day</a> and
<a href="http://veimages.gsfc.nasa.gov/1438/earth_lights_lrg.jpg">Night</a> maps from <a href="http://nasa.gov/">NASA</a>
 | Inspiration from <a href="http://fourmilab.ch/earthview/">Earth and Moon Viewer</a></p>
<p>&copy; 2007-2009 <a href="http://da.vidr.cc/projects/3dglobe/">David Roberts</a></p>
</body>
</html>
