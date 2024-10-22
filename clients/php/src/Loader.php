<?php

/**
 * SONDIX (Simple Open Nuclear Decommissioning Information Exchange) protocol Client for PHP
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 * License: See LICENSE.md file in the root folder of the software package.
 */

namespace SondixPhpClient;

class Loader {

  public string $clientId;              // CLIENT_ID defined in the IAM (Keycloak)
  public string $clientSecret;          // CLIENT_SECRET defined in the IAM (Keycloak)
  public string $userName;              // USER_NAME defined in the IAM (Keycloak)
  public string $userPassword;          // USER_PASSWORD defined in the IAM (Keycloak)

  public string $iamTokenEndpoint;      // OAuth compatible endpoint of the IAM
  public string $sondixEndpoint;        // SONDIX endpoint

  // HTTP client
  public object $guzzle;                // 3rd-party HTTP library
  public object $lastResponse;          // Calue of the last HTTP response
  public string $debugFile;             // Path to the HTTP debug file

  // IAM client
  public string $accessToken;           // Access token received from IAM

  // Miscelaneous
  public string $database;              // Name of the database which will be used
                                        // in the HTTP requests
  
  /**
   * Constructs a SONDIX PHP API client object
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

    $this->iamTokenEndpoint = $config['iamTokenEndpoint'] ?? "";
    $this->sondixEndpoint = $config['sondixEndpoint'] ?? "";

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
      $this->iamTokenEndpoint."/token",
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
   * Send a request to the SONDIX server
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

      $this->lastResponse = $this->guzzle->request(
        $method,
        $this->sondixEndpoint.$command,
        $options
      );

      return $this->lastResponse;
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
      throw new \SondixPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => 503,
          "reason" => "Connection to SONDIX server failed."
        ])
      );
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
      $this->lastResponse = $e->getResponse();
      throw new \SondixPhpClient\Exception\RequestException(
        json_encode([
          "request" => $e->getRequest(),
          "statusCode" => $this->lastResponse->getStatusCode(),
          "reason" => $this->lastResponse->getReasonPhrase(),
          "responseBody" => @json_decode($this->lastResponse->getBody(true))
        ])
      );
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      throw new \SondixPhpClient\Exception\RequestException(
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
      throw new \SondixPhpClient\Exception\RequestException(
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
  
  /**
   * Creates a database.
   *
   * @param  string $database
   * @return string Response body.
   */
  public function createDatabase(string $database): string
  {
    $res = $this->sendRequest("POST", "/database/{$database}");
    return (string) $res->getBody();
  }
  
  /**
   * Sets a database to be used in the requests.
   *
   * @param  string $database
   * @return void
   */
  public function setDatabase(string $database)
  {
    $this->database = $database;
  }

  /**
   * Shortcut to get list of available databases.
   *
   * @return array List of available databases..
   */
  public function getDatabases(): array
  {
    $res = $this->sendRequest("GET", "/databases");
    return json_decode((string) $res->getBody(), TRUE);
  }

  /**
   * Shortcut to delete a database
   *
   * @return string $databaseName of 200 success. Otherwise exception is thrown.
   */
  public function deleteDatabase(): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$this->database}");
    return (string) $res->getBody();
  }

  /**
   * Shortcut to create a record.
   *
   * @param  mixed $recordContent Content of the new record.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createRecord(array $record): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/record", $record);
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to update a record
   *
   * @param  mixed $recordUid UID of the record to update.
   * @param  mixed $newContent New record's content.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateRecord(string $recordUid, array $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/record/{$recordUid}", $newContent);
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to get a record.
   *
   * @param  mixed $recordUid UID of the record to get.
   * @return array Data of the requested record. Otherwise exception is thrown.
   */
  public function getRecord(string $recordUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/record/{$recordUid}");
    return (array) json_decode((string) $res->getBody(), TRUE);
  }
  
  /**
   * Shortcut to delete a record
   *
   * @param  mixed $recordUid UID of the record to delete.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function deleteRecord(string $recordUid): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$this->database}/record/{$recordUid}");
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to get records by a query.
   *
   * @param  mixed $query A MongoDB-like search query.
   * @return array List of records matching the query.
   */
  public function getRecords($query = NULL, $fields = NULL, $methods = NULL): array
  {
    $res = $this->sendRequest(
      "POST", 
      "/database/{$this->database}/records", 
      [
        "query" => $query,
        "flieds" => $fields,
        "methods" => $methods
      ]
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

  /**
   * Shortcut to create a document.
   *
   * @param  mixed $document Content of the new document.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createDocument(array $document): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/document", $document);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to update a document
   *
   * @param  mixed $documentUid UID of the document to update.
   * @param  mixed $newContent New documents's content.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateDocument(string $documentUid, string $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/document/{$documentUid}", ['newContent' => $newContent]);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to get documents by a query.
   *
   * @param  mixed $query A MongoDB-like search query.
   * @return array List of records matching the query.
   */
  public function getDocuments($query = NULL, $fields = NULL, $methods = NULL): array
  {
    $res = $this->sendRequest(
      "POST", 
      "/database/{$this->database}/documents", 
      [
        "query" => $query,
        "flieds" => $fields,
        "methods" => $methods
      ]
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }
}