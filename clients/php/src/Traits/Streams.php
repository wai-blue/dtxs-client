<?php

namespace DtxsPhpClient\Traits;

trait Streams
{

  /**
   * Open a real-time stream.
   *
   * @return string StreamUid in case of 200 success. Otherwise exception is thrown.
   */
  public function openStream(): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/stream", []);
    return (string) $res->getBody();
  }

}