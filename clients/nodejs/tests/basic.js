// load config
require('dotenv').config();

// load the SondixApiClient class
const SondixApiClient = require("../client/client");

// create the SondixApiClient object
client = new SondixApiClient(
  process.env.API_HOST,
  process.env.API_PORT,
  process.env.API_VERSION
);

// get the access token and then send the request
client
  .getAccessToken(
    `${process.env.IAM_OIDC_ENDPOINT}/token`,
    process.env.CLIENT_1_ID,
    process.env.CLIENT_1_SECRET,
    process.env.USER_1_NAME,
    process.env.USER_1_PASSWORD
  )
  .then(
    (response) => {
      client
        .setToken(response.data.access_token)
        .sendRequest('POST', 'project/testProject1a', {})
        .then(
          (response) => {
            console.log('Response:', response.status, response.statusText);
          },
          (error) => {
            console.log('Error:', error.errno, error.code);
            if (error.response) {
              console.log(error.response.status, error.response.statusText, error.response.data, error.response.data.error, error.response.data.error_description);
            }
          }
        )
      ;

    },
    (error) => {
      console.log('Error:', error);
      if (error.response) {
        console.log(error.response.status, error.response.statusText, error.response.data.error, error.response.data.error_description);
      }
    }
  )
;
