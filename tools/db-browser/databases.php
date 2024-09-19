<?php

/**
 * SONDIE DB Browser
 * Utility to browse and manage the content of SONDIE database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

checkIfClientIsLogged();

$databaseToCreate = $_GET['create'] ?? "";
$databaseToEmpty = $_GET['empty'] ?? "";

$databasesPage = $_GET['databases-page'] ?? 1;

echo "
  <header class='mx-auto' style='position:absolute;top:0;left:0;right:0;width:30%'>
    <nav class='navbar-light bg-light rounded-bottom'>
      <ul class='navbar-nav navbar-expand-lg justify-content-center pt-2'>
        <li class='nav-item p-3'>
          <a class='nav-link' href='index.php'><i class='fa-solid fa-house-chimney'></i> Home</a>
        </li>
        <li class='nav-item p-3'>
          <a class='nav-link active-page' href='databases.php'><i class='fa-solid fa-list'></i> Databases</a>
        </li>
        <li class='nav-item p-3'>
          <a class='nav-link text-primary' href='javascript:void()' data-toggle='modal' data-target='#new-database-modal'><i class='fa-solid fa-plus'></i> Add new database</a>
        </li>
      </ul>
    </nav>
  </header>
";

try {
  // Initiate API client
  $api = new \SondiePhpClient\Client\Client(getApiConfig());

  // Get access token
  $api->getAccessToken();

  // Create new database
  if (!empty($databaseToCreate)) {
    try {
      $api->createDatabase($databaseToCreate);
      header("Location: browse.php?db={$databaseToCreate}");
    } catch (\SondiePhpClient\Client\Exception\RequestException $e) {
      $exception = json_decode($e->getMessage(), TRUE);

      echo "
        <script>
          Swal.fire(
            '{$exception['statusCode']}',
            '"
              .(isset($exception["responseBody"]["error"]) 
              ? $exception["responseBody"]["error"] 
              : $exception['reason'])
            ."',
            'warning'
          )
        </script>
      ";
    }
  }

  // Delete database records
  if (!empty($databaseToEmpty)) {
    // database to empty
    $api->setDatabase($databaseToEmpty);
    $allRecords = $api->getRecords(['class' => ['$not' => ['$regex' => 'Database.Information']]]);

    foreach ($allRecords as $record) {
      $api->deleteRecord($record['_id']);
    }
    
    header("Location: databases.php");
  }

  // Get all databases
  $allDatabases = $api->getDatabases(["class" => "Database.Information"]);

  echo "
    <!-- Modal -->
    <div class='modal fade' id='new-database-modal' tabindex='-1' role='dialog' aria-labelledby='new-database-modalLabel' aria-hidden='true'>
      <div class='modal-dialog' role='document'>
        <form method='GET' action='{$_SERVER['PHP_SELF']}'>
          <div class='modal-content'>
            <div class='modal-header'>
              <h5 class='modal-title' id='new-database-modalLabel'>Add new database</h5>
              <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
              </button>
            </div>
            <div class='modal-body'>
              <input 
                class='form-control'
                type='text'
                id='database-name-input'
                name='create'
                value=''
                placeholder='Database name'
              />
              <small 
                id='database-exists' 
                class='text-danger mt-1 ml-1'
                style='display:none'
              >Database already exists</small>
            </div>
            <div class='modal-footer'>
              <button 
                  id='add-new-database-button'
                  class='btn btn-success'
                  style='display:none;'
              >✔ Add new database</button>
              <button 
                id='close-new-database-button'
                class='btn btn-danger'
                data-dismiss='modal'
              >✗ Close</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  ";

  // generate table
  echo "
    <br/>
    <div class='wrapper'>
      <div class='text-center'>
        <h1 class='mb-3 text-primary'>List of databases</h1>
      </div>

      <table id='databases' class='table table-striped table-bordered' style='width:100%'>
        <thead>
          <tr>
            <td style='width:50%'>Database name</td>
            <td>Action</td>
          </tr>
        </thead>
        <tbody>
  ";
    
  foreach ($allDatabases as $key => $database) {
    echo "
      <tr data-database-name='{$database['name']}'>
        <td>{$database['name']}</td>
        <td class='row-buttons'>
          <a href='browse.php?db={$database['name']}&databases-page={$databasesPage}' class='btn btn-primary browse'>Browse</a> 
          <a href='import.php?db={$database['name']}&databases-page={$databasesPage}' class='btn btn-outline-dark import'><i class='fa-solid fa-arrow-up'></i> Import</a>
          <a href='export.php?db={$database['name']}&databases-page={$databasesPage}' class='btn btn-outline-dark export'><i class='fa-solid fa-arrow-down'></i> Export</a>
          <button onclick='clearTable(\"{$database['name']}\")' class='btn btn-danger'>Clear</button>
        </td>
      </tr>
    ";
  }

  echo "
        </tbody>
      </table>
    </div>
  ";

  echo "
    <script>
      $(document).on('keydown', 'form', function(event) { 
        return event.key != 'Enter';
      });

      $('#new-database-modal').on('hidden.bs.modal', function (e) {
        $('#database-name-input').val('');
        $('#add-new-database-button').hide();
        $('#database-name-input').removeClass('is-invalid');
        $('#database-exists').hide();
      })

      $(document).ready(function() {
        var dt = $('#databases')
          .DataTable()
          .page({$databasesPage} - 1)
          .draw(false)
        ;

        dt.on('draw', function () {
          var page = dt.page.info().page + 1;

          let lastPage = {$databasesPage};
          let sliceLength = lastPage.toString().length;

          $('.row-buttons').children('a').each(function() {
            let oldUrl = $(this).attr('href');

            let newUrl = oldUrl.slice(0, - parseInt(sliceLength)) + page;

            $(this).attr('href', newUrl);
          });
        });


        var allDatabases = ".json_encode($allDatabases).";
        $('#database-name-input').val('');

        $('#database-name-input').keyup(() => {
          if ($('#database-name-input').val().length > 0) {
            $('#create-new-database-button').hide();
            $('#add-new-database-button').show();
            $('#database-exists').hide();
            $('#database-name-input').removeClass('is-invalid');

            allDatabases.forEach((database) => {
              if (database.name == $('#database-name-input').val()) {
                $('#add-new-database-button').hide();
                $('#database-exists').show();
                $('#database-name-input').addClass('is-invalid');
              } 
            })
          } else {
            $('#add-new-database-button').hide();
          }
        })
      });

      function highlightTableRow(recordId) {
        $('#records tr').removeClass('highlighted');
        $('#records tr[data-record-id=\"' + recordId + '\"]').addClass('highlighted');
      }

      function clearTable(databaseToClear) {
        Swal.fire({
          title: 'Are you sure?',
          html: 'Are you sure you want to delete all records from <b>' + databaseToClear + '</b>?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete records',
          confirmButtonColor: '#dc3545'
        })
        .then((result) => {
          if (result.isConfirmed) {
            location.href = 'databases.php?empty=' + databaseToClear
          }
        });
      }
    </script>

    
  ";
} catch (\SondiePhpClient\Client\Exception\RequestException $e) {
  $exception = json_decode($e->getMessage(), TRUE);

  echo "
    <script>
      Swal.fire(
        '{$exception['statusCode']}',
        '"
          .(isset($exception["responseBody"]["error"]) 
          ? $exception["responseBody"]["error"] 
          : $exception['reason'])
        ."',
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
        title:'".($responseBody['error_description'] ?? $responseBody['error'])."',
        icon: 'error'
      })
    </script>
  ";
}

require __DIR__."/includes/footer.php";

?>
