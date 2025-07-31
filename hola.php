<?php
echo "PHP FUNCIONA - " . date('Y-m-d H:i:s');
echo "<br>PHP Version: " . PHP_VERSION;
echo "<br>GD Extension: " . (extension_loaded('gd') ? 'YES' : 'NO');
echo "<br>Working Dir: " . getcwd();
if (is_dir('uploads')) echo "<br>Uploads dir: EXISTS";
if (file_exists('uploads/dbdc084939e778491a168dfbd94f14ba.jpg')) {
    echo "<br>Test image: EXISTS (" . filesize('uploads/dbdc084939e778491a168dfbd94f14ba.jpg') . " bytes)";
}
