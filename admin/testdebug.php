<?php
file_put_contents(__DIR__ . '/testdebug.log', date('c') . "\n", FILE_APPEND);
echo 'testdebug'; 