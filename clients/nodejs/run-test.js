/**
 * DTXS API client test runner
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 * License: See LICENSE.md file in the root folder of the software package.
 */

let testToRun = process.argv[2];

if (typeof testToRun === 'undefined') {
  console.log(`Usage: node run-test.js -- <TEST_NAME>`);
  process.exit();
}

import('./tests/' + testToRun + '.js');