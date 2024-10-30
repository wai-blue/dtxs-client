<?php

/**
 * DTXS DB Browser
 * Utility to browse and manage the content of DTXS database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

session_destroy();
header("Location: login.php");