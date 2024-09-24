/**
 * SONDIX client
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 * License: See LICENSE.md file in the root folder of the software package.
 */

const axios = require('axios');

module.exports = class SondixApiClient {
  endpoint = '';
  port = '';
  version = '';
  token = '';

  lastError = null;
  lastResponse = null;

  constructor(endpoint, port, version) {
    this.endpoint = endpoint;
    this.port = port;
    this.version = version;
  }

  getAccessToken(keycloakTokenEndpoint, keycloakClientId, keycloakClientSecret, keycloakUsername, keycloakPassword) {
    // more about OAuth 2.0 Client credentials flow:
    // https://developer.okta.com/docs/concepts/oauth-openid/#client-credentials-flow

    const params = new URLSearchParams();
    params.append("grant_type", "password");
    params.append("client_id", keycloakClientId);
    params.append("username", keycloakUsername);
    params.append("password", keycloakPassword);

    // This is required if the Client in the Keycloak is set to "confidential".
    params.append("client_secret", keycloakClientSecret);

    // send the token request
    return axios({
      "method": "post",
      "url": keycloakTokenEndpoint,
      "headers": {
        'content-type': 'application/x-www-form-urlencoded',
      },
      "data": params,
    });
  }

  buildUrl(path) {
    return 'http://' + this.endpoint + ':' + this.port + '/sondix/api/' + this.version + path + '/';
  }

  setToken(token) {
    this.token = token
    return this;
  }

  sendRequest(method, path, data) {
    console.log(`${method} to ${this.buildUrl(path)}`);
    return axios({
      "method": method,
      "url": this.buildUrl(path),
      "headers": {
        'content-type': 'application/json',
        'authorization': (this.token ? 'Bearer ' + this.token : ''),
      },
      "data": data
    });
  }

  logResponse(response) {
    console.log('Response:', response.status, response.statusText, response.data);
    // console.log(response);
  }

  logError(error) {
    console.log('Error:', error.response.status, error.response.statusText, error.response.data);
  }
}
