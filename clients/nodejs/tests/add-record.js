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

// prepare the record to add
let recordToAdd = {
  'class': 'Test.Class',
  'content': {
    'stringAttribute': 'Hello World.',
    'numberAttribute': 1234,
    'arrayAttribute': ['item1', 'item2'],
  }
};

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
        .sendRequest('GET', '/database/testDatabase/activate')
        .then((response) => {
          client.logResponse(response);
          client
            .sendRequest('POST', '/record', recordToAdd)
            .then(client.logResponse, client.logError)
          ;
        });

    },
    (error) => {
      console.log(error.response.status, error.response.statusText, error.response.data);
    }
  )
;
