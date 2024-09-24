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
      client.setToken(response.data.access_token);

      client
        .sendRequest('PUT', '/database/testDatabase')
        .then(client.logResponse, client.logError)
      ;

    },
    client.logError
  )
;
