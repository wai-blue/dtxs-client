<?php

namespace DtxsPhpClient\Traits;

trait Documents
{

  /**
   * Shortcut to create a document.
   *
   * @param  mixed $folderUid UID of the folder where is document located.
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
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @param  mixed $newContent New documents's content.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateDocument(string $folderUid, string $documentUid, string $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}", ['newContent' => $newContent]);
    return (string) $res->getBody();
  }

  /**
   * Get document's metadata
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function getDocument(string $folderUid, string $documentUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}");
    return json_decode((string) $res->getBody(), true);
  }

  /**
   * Download document
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @return string Content of the document in case of 200 success. Otherwise exception is thrown.
   */
  public function downloadDocument(string $folderUid, string $documentUid): string
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}/download");
    return (string) $res->getBody();
  }

  // /**
  //  * Shortcut to get documents by a query.
  //  *
  //  * @return array List of records matching the query.
  //  */
  public function getDocuments(): array
  {
    $res = $this->sendRequest(
      "POST",
      "/database/{$this->database}/documents"
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}