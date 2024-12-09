<?php

namespace DtxsPhpClient\Traits;

trait Databases
{
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
  public function deleteDatabase($database): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$database}");
    return (string) $res->getBody();
  }
}