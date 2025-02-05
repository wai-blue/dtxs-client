<?php

/**
 * DTXS (Simple Open Nuclear Decommissioning Information Exchange) protocol Client for PHP
 * Author: Dusan Daniska, dusan.daniska@wai.blue
 * License: See LICENSE.md file in the root folder of the software package.
 */

namespace DtxsPhpClient;

class Loader {

  public string $clientId;              // CLIENT_ID defined in the IAM (Keycloak)
  public string $clientSecret;          // CLIENT_SECRET defined in the IAM (Keycloak)
  public string $userName;              // USER_NAME defined in the IAM (Keycloak)
  public string $userPassword;          // USER_PASSWORD defined in the IAM (Keycloak)

  public string $oauthEndpoint;         // OAuth compatible endpoint of the IAM
  public string $dtxsEndpoint;          // DTXS endpoint

  // HTTP client
  public object $guzzle;                // 3rd-party HTTP library
  public object $lastResponse;          // Calue of the last HTTP response
  public string $debugFile;             // Path to the HTTP debug file

  // IAM client
  public string $accessToken;           // Access token received from IAM

  // Miscelaneous
  public string $database;              // Name of the database which will be used
                                        // in the HTTP requests

  public array $lastRequest = [];       // info about last request sent

  /**
   * Constructs a DTXS PHP API client object
   *
   * @param  mixed $config
   * @return void
   */
  public function __construct(array $config)
  {

    // load configuration
    $this->clientId = $config['clientId'] ?? "";
    $this->clientSecret = $config['clientSecret'] ?? "";
    $this->userName = $config['userName'] ?? "";
    $this->userPassword = $config['userPassword'] ?? "";
    $this->debugFile = $config['debugFile'] ?? "";

    $this->oauthEndpoint = $config['oauthEndpoint'] ?? "";
    $this->dtxsEndpoint = $config['dtxsEndpoint'] ?? "";

    $this->database = '';

    // initiate HTTP client
    $this->guzzle = new \GuzzleHttp\Client(['verify' => false]);

  }

  /**
   * Fetches access token from the IAM (Keycloak)
   *
   * @return string Access token received from the IAM (Keycloak)
   */
  public function getAccessToken(): string
  {
    $response = $this->guzzle->request(
      "POST",
      $this->oauthEndpoint."/token",
      [
        'headers' => [
          'content-type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => [
          'grant_type' => 'password',
          'client_id' => $this->clientId,
          'client_secret' => $this->clientSecret,
          'username' => $this->userName,
          'password' => $this->userPassword,
        ]
      ]
    );

    $responseJson = @json_decode((string) $response->getBody(), TRUE);

    $this->setAccessToken($responseJson['access_token'] ?? "");

    return $this->accessToken;
  }

  public function setAccessToken(string $accessToken)
  {
    $this->accessToken = $accessToken;
  }
  
  /**
   * Send a request to the DTXS server
   *
   * @param  mixed $method HTTP method (GET/POST/PUT/DELETE)
   * @param  mixed $command A command (API function) to call (e.g. "/database/DB_NAME/record/RECORD_ID")
   * @param  mixed $body Array of request's body parameters.
   * @return object Guzzle's HTTP response object.
   */
  public function sendRequest(string $method, string $command, array $body = [])
  {
    try {
      $options = [
        'headers' => [
          'content-type' => 'application/json',
          'authorization' => "Bearer {$this->accessToken}",
        ],
        'body' => json_encode($body),
      ];

      if (!empty($this->debugFile)) $options['debug'] = fopen($this->debugFile, 'w');

      $this->lastRequest = [
        'method' => strtoupper($method),
        'endpoint' => $this->dtxsEndpoint.$command,
        'body' => $body
      ];

      $this->lastResponse = $this->guzzle->request(
        $method,
        $this->dtxsEndpoint.$command,
        $options
      );

      return $this->lastResponse;
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
      throw new \DtxsPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => 503,
          "reason" => "Connection to DTXS server failed."
        ])
      );
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
      $this->lastResponse = $e->getResponse();
      throw new \DtxsPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => $this->lastResponse->getStatusCode(),
          "reason" => $this->lastResponse->getReasonPhrase(),
          "responseBody" => @json_decode($this->lastResponse->getBody(true))
        ])
      );
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      throw new \DtxsPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => 500,
          "reason" => "General RequestException error."
        ])
      );
    } 
  }

  public function sendJsonRequest(string $jsonRequest)
  {
    try {
      $request = json_decode($jsonRequest, true);

      $method = $request['method'] ?? '';
      $command = $request['command'] ?? '';
      $body = $request['body'] ?? [];

      return $this->sendRequest($method, $command, $body);
    } catch (\Exception $e) {
      throw new \DtxsPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => 500,
          "reason" => "General RequestException error."
        ])
      );
    }
  }
  
  /**
   * Checks permissions for a given operation.
   *
   * @param  string $operation
   * @return string Response body
   */
  public function checkPermissions(string $operation)
  {
    $res = $this->sendRequest("GET", "/checkPermissions/{$operation}");
    return (string) $res->getBody();
  }
  
  /* Shortcut to get information about the server
   *
   * @return array Information about the server
   */
  public function getServerInfo(): array
  {
    $res = $this->sendRequest("GET", "/server-info");
    return json_decode((string) $res->getBody(), TRUE);
  }

  /* Shortcut to get list of available classes.
   *
   * @return array List of available classes.
   */
  public function getClasses(): array
  {
    $res = $this->sendRequest("GET", "/classes");
    return json_decode((string) $res->getBody(), TRUE);
  }


  use Traits\Databases;
  use Traits\Records;
  use Traits\Folders;
  use Traits\Documents;

}