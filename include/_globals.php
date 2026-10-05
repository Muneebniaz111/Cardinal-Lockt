<?php

if (!defined('SESSION_SAVE_PATH')) {
    define('SESSION_SAVE_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lockt-cardinal-sessions');
}

if (!is_dir(SESSION_SAVE_PATH)) {
    mkdir(SESSION_SAVE_PATH, 0700, true);
}