<?php

set_error_handler(static function ($severity, $message, $file, $line) {
    error_log(sprintf('%s in %s on line %d', $message, $file, $line));
    return true;
});