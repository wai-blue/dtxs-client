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

checkIfClientIsLogged();

$database = $_GET['db'] ?? "";
$databaseToDelete = $_GET['delete'] ?? "";

$browsePage = $_GET['browse-page'] ?? 1;
$databasesPage = $_GET['databases-page'] ?? 1;
$scrollToId = $_GET['scroll-to-id'] ?? null;

$displayStart = $_GET['display-start'] ?? 0;
$pageLength = $_GET['page-length'] ?? 10;

try {
  // initiate API client
  $api = new \DtxsPhpClient\Client\Client(getApiConfig());

  // get access token
  $api->getAccessToken();

  if (!empty($databaseToDelete)) {
    $api->setDatabase($databaseToDelete);
    $api->deleteDatabase();
    header("Location: databases.php");
  }

  if ($database != "") {
    echo "
      <header class='mx-auto' style='position:absolute;top:0;left:0;right:0;width:30%'>
        <nav class='navbar-light bg-light rounded-bottom'>
          <ul class='navbar-nav navbar-expand-lg justify-content-center pt-2'>
            <li class='nav-item p-3'>
              <a class='nav-link' href='index.php'><i class='fa-solid fa-house-chimney'></i> Home</a>
            </li>
            <li class='nav-item p-3'>
              <a class='nav-link' href='databases.php'><i class='fa-solid fa-list'></i> Databases</a>
            </li>
            <li class='nav-item p-3 text-primary'>
              <a class='nav-link text-primary' href='create.php?db={$database}'><i class='fa-solid fa-plus'></i> Add new record</a>
            </li>
          </ul>
        </nav>
      </header>
    ";
  } else {
    echo "
      <header class='mx-auto' style='position:absolute;top:0;left:0;right:0;width:30%'>
        <nav class='navbar-light bg-light rounded-bottom'>
          <ul class='navbar-nav navbar-expand-lg justify-content-center pt-2'>
            <li class='nav-item p-3'>
              <a class='nav-link' href='index.php'><i class='fa-solid fa-house-chimney'></i> Home</a>
            </li>
            <li class='nav-item p-3'>
              <a class='nav-link' href='databases.php'><i class='fa-solid fa-list'></i> Databases</a>
            </li>
          </ul>
        </nav>
      </header>
      <div class='row'>
        <div class='col'>
          <p class='h2'>
            Database: <i class='text-secondary'>is not selected</i>
            &nbsp;<a href='databases.php' class='btn btn-primary'>Select database</a>
          </p>
        </div>
      </div>
    ";
    exit();
  }

  // activate database
  $res = $api->setDatabase($database);

  // get all records
  $allRecords = $api->getRecords();

  // generate table
  echo "
    <div id='cover-spin'></div>
    </br>
    <div class='wrapper'>
      <a 
        href='databases.php?databases-page={$databasesPage}'
        class='btn btn-primary'
      ><i class='fa-solid fa-arrow-left'></i> Back to list of databases</a>
      <button 
        onclick='deleteDatabase()' 
        class='btn btn-danger 
        float-right'
      ><i class='fa-solid fa-trash'></i> Delete database</button>
      <div class='text-center mt-5'>
        <p class='h1 mb-3 text-primary'>List of records from <b>{$database}</b></p>
      </div>

      <table id='records' class='table table-striped table-bordered' style='width:100%;'>
      </table>
  ";
  
  echo "
    <script>

      $(document).ready(function() {
        
        var dt = $('#records')
          .DataTable({
            columns: [
              { title: '_id', data: '_id', render: function(data) {
                return `<a href='record.php?db={$database}&_id=` + data + `' target=_blank>
                  ` + data + `
                </a>`;
              }},
              { title: 'Class', data: 'class' },
              { title: 'Content', data: 'content', render: function(data) {
                return `<div class='content-wrapper'><pre style='white-space:pre-wrap !important;font-family:courier;margin:0'>` + data + `</pre></div>`;
              }},
              { title: 'Content.RecordInfo', data: 'recordInfo', render: function(data) {
                return `<div class='content-wrapper'><pre style='white-space:pre-wrap;font-family:courier;margin:0'>` + data + `</pre></div></div>`;
              }},
              { title: '', data: 'editButton', orderable: false },
            ],
            ajax: 'api/browse/data.php?database={$database}',
            serverSide: true,
            pageLength: {$pageLength},
            displayStart: {$displayStart},
            createdRow: function(row, data, dataIndex) { 
              $(row).attr('data-record-id', data._id);
            },
            fnDrawCallback: () => {
              let scrollToId = '{$scrollToId}';

              if($('tr[data-record-id|=\"' + scrollToId + '\"]').length != 0) {
                $('html, body').animate({
                  scrollTop: ($('tr[data-record-id|=\"' + scrollToId + '\"]').offset().top -500)
                }, 1000);
              } else {
                $('html, body').animate({
                  scrollTop: ($('#records').offset().top -100)
                }, 100);
              }
            }
          })
        ;

        dt.on('processing.dt', function (e, settings, processing) {
          $('#cover-spin').css('display', 'none');
         
          if (processing) {
            $('#cover-spin').show();
          } else {
            $('#cover-spin').hide();
          }
        })

        $('.dataTables_filter input')
        .unbind()
        .bind('keypress keyup', function(e) {
          if (e.keyCode != 13) return;

          dt.search($(this).val()).draw();
        });
      });

      function highlightTableRow(recordId) {
        $('#records tr').removeClass('highlighted');
        $('#records tr[data-record-id=\"' + recordId + '\"]').addClass('highlighted');
      }

      function deleteDatabase() {
        Swal.fire({
          title: 'Are you sure?',
          html: 'Are you sure you want to delete this database?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete database',
          confirmButtonColor: '#dc3545'
        })
        .then((result) => {
          if (result.isConfirmed) {
            location.href = 'browse.php?delete={$database}' 
          }
        });
      }
    </script>
  ";
} catch (\DtxsPhpClient\Client\Exception\RequestException $e) {
  $exception = json_decode($e->getMessage(), TRUE);

  echo "
    <script>
      Swal.fire(
        '{$exception['statusCode']}',
        '{$exception['reason']}',
        'question'
      )
    </script>
  ";
} catch (\GuzzleHttp\Exception\ConnectException $e) {
    echo "
      <script>
        Swal.fire(
          'Unable to get ACCESS TOKEN',
          '".$e->getMessage()."',
          'question'
        )
      </script>
    ";
} catch(\GuzzleHttp\Exception\ClientException $e) {
  $responseBody = @json_decode($e->getResponse()->getBody(true), TRUE);
  echo "
    <script>
      Swal.fire({
        title:'{$responseBody['error_description']}',
        icon: 'error'
      })
    </script>
  ";
}

require __DIR__."/includes/footer.php";
