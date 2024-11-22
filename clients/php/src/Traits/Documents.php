<?php

namespace DtxsPhpClient\Traits;

trait Documents
{

  /**
   * Shortcut to create a document.
   *
   * @param  mixed $document Content of the new document.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createDocument(string $folderUid, array $document): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/folder/{$folderUid}/document", $document);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to update a document
   *
   * @param  mixed $documentUid UID of the document to update.
   * @param  mixed $newContent New documents's content.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateDocument(string $folderUid, string $documentUid, string $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}", ['newContent' => $newContent]);
    return (string) $res->getBody();
  }

  // /**
  //  * Shortcut to get documents by a query.
  //  *
  //  * @param  mixed $query A MongoDB-like search query.
  //  * @return array List of records matching the query.
  //  */
  // public function getDocuments($query = NULL, $fields = NULL, $methods = NULL): array
  // {
  //   $res = $this->sendRequest(
  //     "POST", 
  //     "/database/{$this->database}/documents", 
  //     [
  //       "query" => $query,
  //       "flieds" => $fields,
  //       "methods" => $methods
  //     ]
  //   );

  //   return (array) json_decode((string) $res->getBody(), TRUE);
  // }

}