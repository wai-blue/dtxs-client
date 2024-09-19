<?php

/**
 * SONDIX DB Browser
 * Utility to browse and manage the content of SONDIX database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require(__DIR__."/vendor/autoload.php");
require(__DIR__."/includes/lib.php");
require(__DIR__)."/config.php";

session_start(); 

