<?php

/* 
  * SONDIX API Classes Visualiser
  *
  * Tool for streamlining the collaboration in developing the SONDIX
  * API Classes structure and properties.
  *
  * Author: Dusan Daniska, dusan.daniska@wai.sk 
  *
  */

require(__DIR__."/../vendor/autoload.php");
require(__DIR__."/../common.php");
require(__DIR__."/../lib.php");

use \Symfony\Component\Yaml\Yaml;

$classesTree = Yaml::parse(file_get_contents(__DIR__."/../api-classes.yml"));
$classNameToDisplay = $_GET['c'] ?? "";
$highlightedProperty = "";
$outputFormat = $_GET['f'] ?? "html";

if (!in_array($outputFormat, ["html", "pdf"])) $outputFormat = "html";

if (strpos($classNameToDisplay, ":") !== FALSE) {
  [$classNameToDisplay, $highlightedProperty] = explode(":", $classNameToDisplay);
}


////////////////////////////////////////////////////////////////////////////////////////////////

// Render necessary HTML parts
[$treeHtml, $detailsHtml, $notesHtml] = renderClassesTree(
  $classesTree,
  $classNameToDisplay,
  $highlightedProperty,
  $outputFormat
);

$flatClassesList = flatizeClassesTree($classesTree);

// Compile and print out the complete HTML

echo \Common::HtmlHeader("Classes and properties", $outputFormat);

echo "
  <script>

    function isVisible(ele, container) {
      const eleTop = ele.offsetTop;
      const eleBottom = eleTop + ele.clientHeight;

      const containerTop = container.scrollTop;
      const containerBottom = containerTop + container.clientHeight;

      // The element is fully visible in the container
      return (
        (eleTop >= containerTop && eleBottom <= containerBottom) ||
        // Some part of the element is visible in the container
        (eleTop < containerTop && containerTop < eleBottom) ||
        (eleTop < containerBottom && containerBottom < eleBottom)
      );
    }

    function scrollIntoViewIfOutOfView(el) {
      var topOfPage = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
      var heightOfPage = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
      var elY = 0;
      var elH = 0;
      if (document.layers) { // NS4
          elY = el.y;
          elH = el.height;
      }
      else {
          for(var p=el; p&&p.tagName!='BODY'; p=p.offsetParent){
              elY += p.offsetTop;
          }
          elH = el.offsetHeight;
      }
      if ((topOfPage + heightOfPage) < (elY + elH)) {
          el.scrollIntoView(false);
      }
      else if (elY < topOfPage) {
          el.scrollIntoView(true);
      }
  }

    function scrollToActiveClassButton() {
      if (!isVisible($('.api-classes-tree .btn-primary').get(0), $('.api-classes-tree').get(0))) {
        // scrollIntoViewIfOutOfView($('.api-classes-tree .btn-primary').get(0));
      }
    }

    function showClassDetails(className) {
      className = className.replaceAll('.', '-').replaceAll('$', '_');

      if (className.indexOf(':') != -1) {
        let tmp = className.split(':');
        className = tmp[0];
        propertyName = tmp[1];
      } else {
        propertyName = '';
      }

      $('.api-class-detail').hide();
      $('#' + className + '_detail').show();

      $('.api-class-button').removeClass('btn-primary').addClass('btn-light');
      $('#' + className + '_button').addClass('btn-primary').removeClass('btn-light');

      $('.api-class-detail .table tr').removeClass('highlighted');

      if (propertyName) {
        $('.api-class-detail .table tr#property-' + className + '-' + propertyName).addClass('highlighted');
      }

      let tmp = className + (propertyName ? ', ' + propertyName : '');
      window.history.pushState(tmp, tmp, '?c=' + className + (propertyName ? ':' + propertyName : ''));

      scrollToActiveClassButton();
    }

    function searchClass(q) {
      let buttons = $('.api-class-button').closest('div');

      if (q == '') {
        buttons.show();
        return;
      }

      buttons
        .hide()
        .each(function() {
          let buttonText = $(this).find('.btn').get(0).innerText;

          if (
            buttonText.toLowerCase().indexOf(q.toLowerCase()) !== -1
          ) {
            $(this).show();
          }
        })
      ;
    }
  </script>

  <style>
    body { padding: 1em; }

    .api-classes-tree {
      height: calc(100vh - 260px);
      overflow: auto;
    }

    .api-class-detail.html {
      display: none;
      height: calc(100vh - 170px);
      overflow: auto;
    }

    .api-class-detail.pdf {
      margin-bottom: 1em;
      margin-top: 2em;
    }

    .api-class-button.changed {
      border: 1px solid purple;
      color: purple !important;
    }

    .api-class-detail .table tr.highlighted { background: yellow; }
    .api-class-detail .table tr.deprecated { text-decoration: line-through; color: red; }
    .api-class-detail .table tr.default { background: #F3F3F3; }

    .property-data-type { color: #888888; }
    .property-data-unit { color: #5fc535; }
    .property-data-note { background: var(--warning); }
    .property-data-deprecated { text-decoration: line-through; color: red; }
    .property-data-changelog { background: purple; color: white; }
    .property-data-example { color: #333333; font-weight: bold; }
    .property-data-examples { color: #777777; }
    .property-data-validvalues { color: green; }

    .example-value {
      background: #212529;
      color: white;
      padding: 1em;
      max-width: 850px;
      overflow: auto;
      font-size: 1em;
    }

  </style>

  <div style='display:flex;padding:1em;'>
    <div style='flex:1;margin-right:1em;".($outputFormat == "pdf" ? "display:none" : "")."'>
      <div>
        <div class='input-group mb-3'>
          <div class='input-group-prepend'>
            <span class='input-group-text' id='basic-addon1'>🔍</span>
          </div>
          <input
            type='text'
            class='form-control'
            placeholder='Search class...'
            aria-label='Search class...'
            aria-describedby='basic-addon1'
            onkeyup='searchClass($(this).val());'
          >
        </div>
        <div class='card-body api-classes-tree'>
          {$treeHtml}
        </div>
        <a
          href='javascript:void(0)'
          onclick='
            $(\".api-class-detail\").hide();
            $(\"#all_notes\").show();
          '
          class='pt-2 d-block'
        />Show all notes</a>
      </div>
    </div>
    <div style='flex:4'>
      ".(empty($notesHtml) ? "" : "
        <div
          id='all_notes'
          class='api-class-detail {$outputFormat} card'
          style='".(empty($classNameToDisplay) ? "display:block;" : "")."'
        >
          <div class='card-body'>
            <p>{$notesHtml}</p>
          </div>
        </div>
      ")."
      {$detailsHtml}
    </div>
  </div>
  <script>
    $(document).ready(function() {
      scrollToActiveClassButton();
    });

    window.onpopstate = function (e) {
      window.location.reload();
    }
  </script>
";

echo \Common::HtmlFooter();