<?php

/**
 * SONDIX DB Browser
 * Utility to browse and manage the content of SONDIX database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
//require __DIR__."/includes/header.php";

checkIfClientIsLogged();

echo "
  <html>
  <head>
    <title>SONDIX DB Browser</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css'>
    <link rel='stylesheet' href='https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css'/>
    <link rel='stylesheet' href='./style.css'/>
    <style>
      body { padding: 1em }
      .content-wrapper { font-size: 0.8em; }
    </style>
  </head>
  <body>
  <div class='row mb-5'>
    <div class='col-9'>
      <img src='files/logo.png' alt='sondix-logo'/>
    </div>
    ".(!empty(getClientData()) ? "
      <div class='col-3 text-right pt-2'>
        <a href='userinfo.php' class='btn btn-light'>
          <i class='fa-solid fa-user'></i> ".getClientData()['userName']."
        </a>
        <a href='logout.php' class='btn btn-light'>
          <i class='fa-solid fa-arrow-right-from-bracket'></i> Logout
        </a>
      </div>
    " : "")."
  </div>
  <header class='mx-auto' style='position:absolute;top:0;left:0;right:0;width:30%'>
    <nav class='navbar-light bg-light rounded-bottom'>
      <ul class='navbar-nav navbar-expand-lg justify-content-center pt-2'>
        <li class='nav-item p-3'>
          <a class='nav-link active-page' href='index.php'><i class='fa-solid fa-house-chimney'></i> Home</a>
        </li>
        <li class='nav-item p-3'>
          <a class='nav-link' href='databases.php'><i class='fa-solid fa-list'></i> Databases</a>
        </li>
      </ul>
    </nav>
  </header>
  <div class='container text-center'>
    <h1>SONDIX DB browser</h1>
    <h3 class='mt-1'>Version 1.1.0</h3>
  </div>
";
