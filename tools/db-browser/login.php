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
require __DIR__."/includes/header.php";

try {

  if (!empty(getClientData())) {
    header("Location: userinfo.php");
  }

  echo "
    <div class='container mt-5'>
      <div class='card'>
        <div class='card-body'>
          <form method='POST' action='{$_SERVER['PHP_SELF']}'>
            <div class='form-group'>
              <label for='userName'>Username</label>
              <input 
                type='text' 
                name='userName'
                class='form-control' 
                id='userName' 
                placeholder='Username'
                required
              >
            </div>
            <div class='form-group'>
              <label for='userPassword'>User password</label>
              <input 
                type='password' 
                name='userPassword'
                class='form-control' 
                id='userPassword' 
                placeholder='User password'
                required
              >
            </div>
            <input type='submit' name='submitLogin' class='btn btn-primary' value='Sign In'/>
          </form>
        </div>
      </div>
    </div>
  ";

  if (isset($_POST["submitLogin"])) {
    try {
      $clientData = [
        "userName" => isset($_POST["userName"]) ? $_POST["userName"] : "",
        "userPassword" => isset($_POST["userPassword"]) ? $_POST["userPassword"] : ""
      ];

      $apiConfig = array_merge($clientData, $apiConfig);

      // initiate API client
      $api = new \SondixPhpClient\Client\Client($apiConfig);

      // get access token
      $api->getAccessToken();

      // set client data
      setClientData($clientData);

      header("Location: databases.php");
    } catch (\SondixPhpClient\Client\Exception\RequestException $e) {
      throw new \SondixPhpClient\Client\Exception\RequestException($e->getMessage());
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
    }
  }
} catch (\SondixPhpClient\Client\Exception\RequestException $e) {
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
  $response = $e->getResponse();
  echo "
    <script>
      Swal.fire({
        title:'".$response->getReasonPhrase()."',
        icon: 'error',
        confirmButtonText: 'Try again',
      })
    </script>
  ";
}

require __DIR__."/includes/footer.php";