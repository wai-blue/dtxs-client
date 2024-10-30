<?php

class Common {
  public static function HtmlHeader(string $title, string $outputFormat = "html") : string {
    return "
      <html>
      <head>
        <title>DTXS API Classes</title>
        <link
          rel='stylesheet'
          href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css'
          integrity='sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO'
          crossorigin='anonymous'
        >
        <script
          src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js'
          integrity='sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy'
          crossorigin='anonymous'
        ></script>
        <script
          src='https://code.jquery.com/jquery-3.6.0.slim.min.js'
          integrity='sha256-u7e5khyithlIdTpu22PHhENmPcRdFiHRjhAuHcs05RI='
          crossorigin='anonymous'
        ></script>
      </head>
      <body ".($outputFormat == "pdf" ? "onload='window.print();'" : "").">
        <h1>{$title}</h1>
        <h4 class='text-secondary'>DTXS API Classes</h4>
        
    ";
  }

  public static function HtmlFooter() :string {
    return "
        </body>
      </html>
    ";
  }

}