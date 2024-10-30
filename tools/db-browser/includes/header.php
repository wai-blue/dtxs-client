<?php

/**
 * DTXS DB Browser
 * Utility to browse and manage the content of DTXS database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

echo "
  <html>
  <head>
    <title>DTXS DB Browser</title>
    <script src='https://code.jquery.com/jquery-3.5.1.js'></script>
    <script src='https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'></script>
    <script src='https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@json-editor/json-editor@latest/dist/jsoneditor.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.8/sweetalert2.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css'>
    <link rel='stylesheet' href='https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css'/>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.8/sweetalert2.min.css'/>
    <link rel='stylesheet' href='./style.css'/>
    <style>
      body { padding: 1em }
      .content-wrapper { font-size: 0.9em; }
      table#records tr.highlighted { background: yellow; }
    </style>
  </head>
  <body>
    <div class='row mb-5'>
      <div class='col-9'>
        <h3>DTXS DB browser</h3>
        <div class='muted mt-2'>Version 1.1.0</div>
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
";