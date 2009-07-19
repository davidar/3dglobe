<?php
## Copyright (C) 2007-2009  David Roberts <d@vidr.cc>
##
## This program is free software; you can redistribute it and/or
## modify it under the terms of the GNU General Public License
## as published by the Free Software Foundation; either version 2
## of the License, or (at your option) any later version.
##
## This program is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with this program; if not, write to the Free Software
## Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
## 02110-1301, USA.

/** the coordinate system used is a right-handed system in the following
    orientation:
                                z
                                |  y
                                | /
                                |/
                                +----x                                      **/

$res = 256; // dimensions of image

function get_rgb($col) {
    return array(
        'r' => ($col >> 16) & 0xFF,
        'g' => ($col >>  8) & 0xFF,
        'b' =>  $col        & 0xFF
    );
}

function mix($c1, $c2, $amount) {
    $iamount = 1 - $amount;
    return array(
        'r' => $iamount * $c1['r'] + $amount * $c2['r'],
        'g' => $iamount * $c1['g'] + $amount * $c2['g'],
        'b' => $iamount * $c1['b'] + $amount * $c2['b']
    );
}

function paint_pix(&$img, $col, $x, $y) {
    $col = imagecolorallocate($img, $col['r'], $col['g'], $col['b']);
    imagefilledrectangle($img, $x, $y, $x, $y, $col);
}

function time_utc($time = false) {
    if($time === false) $time = time();
    return $time - date("Z");
}

function get_map_pix($mname, $lat, $long) {
    static $map = array();
    
    if(!array_key_exists($mname, $map)) {
        $map[$mname]['img'] = imagecreatefromjpeg("images/$mname.jpg");
        $map[$mname]['x'] = imagesx($map[$mname]['img']);
        $map[$mname]['y'] = imagesy($map[$mname]['img']);
    }
    
    $long += 180;
    $lat = 90 - $lat;
    
    while($long >= 360) $long -= 360;
    while($long <    0) $long += 360;
    while($lat  >= 180) $lat  -= 180;
    while($lat  <    0) $lat  += 180;
    
    return get_rgb(imagecolorat($map[$mname]['img'],
        $long * $map[$mname]['x'] / 360,
        $lat  * $map[$mname]['y'] / 180
    ));
}

function sun_orient($time) {
    // convert unix timestamp to julian days before epoch J2000
    $year = date("Y", $time);
    $month = date("n", $time);
    $day = date("j", $time);
    $J2000 = 2451545.0;
    $d =  gregoriantojd($month, $day, $year) - $J2000;
    
    // from <http://www.stargazing.net/kepler/sun.html>
    $L = 280.461 + 0.9856474 * $d; // mean longitude
    $g = 357.528 + 0.9856003 * $d; // mean anomaly
    // ecliptic longitude:
    $lambda = $L + 1.915 * sin(deg2rad($g)) + 0.020 * sin(deg2rad(2*$g));
    // obliquity of the ecliptic plane:
    $epsilon = 23.439 - 0.0000004 * $d;
    // declination:
    $delta = rad2deg(asin(sin(deg2rad($epsilon))*sin(deg2rad($lambda))));
    
    return $delta;
}

function sun_coord($time) {
    $lat = sun_orient($time); // calculate declination of sun
    $long = 180.0 - 15.0 * (date("G", $time)
                          + date("i", $time)/60.0
                          + date("s", $time)/3600.0);
    return array($lat, $long);
}

function cart2spher($x, $z, $fin_lat, $fin_long) {
    $x2 = $x*$x; // x^2
    $z2 = $z*$z; // z^2
    
    $r = 1; // radius
    $r2 = 1; // r^2
    
    $y = sqrt($r2 - $x2 - $z2); // calculate depth of pixel
    
    // if pixel doesn't pass through globe just return original coords
    if(is_nan($y)) return array($fin_lat, $fin_long);
    
    $theta = rad2deg(atan2($z, $y)); // inclination from xy-plane
    
    // radius of circle obtained by cutting the sphere with a plane which is
    // parallel to the yz-plane and intersects with the x-axis at $x
    $r_yz = sqrt($r2 - $x2);
    
    // rotate globe around x-axis
    $new_y = $r_yz * cos(deg2rad(-$fin_lat + $theta));
    $new_z = $r_yz * sin(deg2rad(-$fin_lat + $theta));
    
    // calculate lat/long of pixel
    $lat = rad2deg(acos($new_z/sqrt($x2+$new_y*$new_y+$new_z*$new_z))) - 90;
    $long = -rad2deg(atan2($new_y, $x)) + $fin_long + 90;
    
    // wrap around longitude
    while($long >  180) $long -= 360;
    while($long < -180) $long += 360;
    
    return array($lat, $long);
}

function gpixcol($lat, $long, $sun, $cmap) {
    $normal_amount = 0.5;
    
    // haversine formula
    $a = sin(deg2rad(($lat-$sun['lat'])/2.0));
    $b = sin(deg2rad(($long-$sun['long'])/2.0));
    $shade = ($a*$a)+cos(deg2rad($lat))*cos(deg2rad($sun['lat']))*($b*$b);
    
    /** $shade = 0.0 : directly beneath sun
        $shade = 0.5 : sunrise/sunset line
        $shade = 1.0 : opposite side of earth to the sun
        theta = 2*asin(sqrt($shade))                     **/
    
    $penumbra = 0.1;
    // distance of penumbra either side of shadow line:
    $half_penumbra = $penumbra / 2.0; 
    
    if(($cmap == "shade" && $shade <= 0.5 - $half_penumbra) ||
        $cmap == "day") { // day
        return get_map_pix("day", $lat, $long);
    } elseif(($cmap == "shade" && $shade >= 0.5 + $half_penumbra) ||
              $cmap == "night") { // night
        return get_map_pix("night", $lat, $long);
    } else { // mix
        $day_colour =  get_map_pix("day", $lat, $long);
        $night_colour = get_map_pix("night", $lat, $long);
        $amount = ($shade - (0.5 - $half_penumbra)) / $penumbra;
        return mix($day_colour, $night_colour, $amount);
    }
}

function paint_globe($fin_lat, $fin_long, $time, $img_dim, $cmap) {
    $img = imagecreatetruecolor($img_dim-1, $img_dim-1);
    $r = $img_dim / 2; // radius of globe
    list($sun['lat'], $sun['long']) = sun_coord($time);
    
    for($x = -$r + 1; $x < $r; $x++) {
        for($z = -$r + 1; $z < $r; $z++) {
            $x_sc = $x/$r;
            $z_sc = $z/$r;
            
            if($x_sc*$x_sc + $z_sc*$z_sc <= 1) {
                // point falls on globe
                list($lat, $long) = cart2spher($x_sc, $z_sc,
                                               $fin_lat, $fin_long);
                $pixcolour = gpixcol($lat, $long, $sun, $cmap);
                paint_pix($img, $pixcolour, $x+$r-1, $z+$r-1);
            }
        }
    }
    
    return $img;
}

function paint_flat($time, $cmap) {
    $img = imagecreatetruecolor(360, 180);
    list($sun['lat'], $sun['long']) = sun_coord($time);
    
    for($lon = -180; $lon < 180; $lon++) {
        for($lat = -90; $lat < 90; $lat++) {
            $pixcolour = gpixcol($lat, $lon, $sun, $cmap);
            paint_pix($img, $pixcolour, $lon+180, -$lat+90);
        }
    }
    
    return $img;
}

function get_lat_lon($res) {
    $lat = (double)($_GET['lat']);
    $lon = (double)($_GET['lon']);
    
    // set defaults
    if(($lat == 0 || is_nan($lat)) && $_GET['lat'] != '0') $lat = -25.0;
    if(($lon == 0 || is_nan($lon)) && $_GET['lon'] != '0') $lon = 135.0;
    
    if($_GET['t'] == "s") {
        // view from sun
        return sun_coord(time_utc());
    } elseif($_GET['map_x'] != '' && $_GET['map_y'] != '') {
        // user clicked on globe
        $x = (int)($_GET['map_x'])*2/$res - 1;
        $y = (int)($_GET['map_y'])*2/$res - 1;
        return cart2spher($x, $y, $lat, $lon);
    } else {
        return array($lat, $lon);
    }
}

list($lat, $lon) = get_lat_lon($res);
$cmap = $_GET['cmap'];
if($cmap != "day" && $cmap != "night") $cmap = "shade";
$flat = ($_GET['p'] == "flat");
?>
