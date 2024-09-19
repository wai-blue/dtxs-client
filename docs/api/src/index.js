import SwaggerUI from 'swagger-ui'
import 'swagger-ui/dist/swagger-ui.css';

const spec = require('../sondix-api-specification.yaml');

const ui = SwaggerUI({
  spec,
  dom_id: '#swagger',
  deepLinking: true,
  docExpansion: 'list', // 'full', 'list'
});

ui.initOAuth({
  appName: "Swagger UI Webpack Demo",
  // See https://demo.identityserver.io/ for configuration details.
  clientId: 'implicit'
});
