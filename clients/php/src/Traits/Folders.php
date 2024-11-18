<?php

namespace DtxsPhpClient\Traits;

trait Folders
{

  /**
   * Shortcut to create a folder.
   *
   * @param  mixed $folderName Name of the new folder.
   * @return string FolderUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createFolder(string $folderName): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/folder", ["FolderName" => $folderName]);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to rename a folder
   *
   * @param  mixed $folderUid UID of the folder to rename.
   * @param  mixed $newFolderName New folders's name.
   * @return string FolderUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateFolder(string $folderUid, string $newFolderName): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/folder/{$folderUid}", ['newFolderName' => $newFolderName]);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to get folders by a query.
   *
   * @return array List of folders matching the query.
   */
  public function getFolders(): array
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/folders");

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}