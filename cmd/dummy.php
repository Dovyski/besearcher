<?php

/**
 * This script does nothing, it only illustrates how Besearcher tags can be used
 * to track the progress of tasks or inform things.
 */

echo 'Dummy is starting: ' . time() . "\n";

echo '[BSR] {"type": "progress", "data": 0.1}' . "\n";
sleep(15);

echo '[BSR] {"type": "progress", "data": 0.5}' . "\n";
sleep(15);

echo '[BSR] {"type": "progress", "data": 0.9}' . "\n";
sleep(15);

echo '[BSR] {"type": "result", "name": "Accuracy", "data": '.(rand(0, 400) / 400).'}' . "\n";
echo '[BSR] {"type": "result", "name": "Precision", "data": '.(rand(0, 400) / 400).'}' . "\n";

echo '[BSR] {"type": "progress", "data": 1.0}' . "\n";

?>
