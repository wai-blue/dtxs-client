# This script executes activities described in
# use case #1 in DTXS documentaion on
# http://localhost/wai_blue/docs/dtxs-digital-twin-data-exchange-standard/api/use-cases/task-planning-risk-assessment-output-analysis

import os
import sys
import json
from clients.python.dtxs_client.main import DtxsClient

if (len(sys.argv) <= 2):
  prYellow("Usage: python -m tests.test_triple <configFile> <dbName>")
  prYellow("")
  prYellow("  configFile     Configuration of OAuth and DTXS endpoints")
  prYellow("  dbName         Name of the database where records will be manipulated with")
  sys.exit()

configFile = sys.argv[1]
dbName = sys.argv[2]

with open(configFile) as f: config = json.load(f)
print('#1')
client = DtxsClient(config['dtxsClient'])
print('#2')
client.getAccessToken()
print('#3 ' + client.accessToken)
client.database = dbName
print('#4')
rRiskUid1 = client.createRecord('Risks', { "Name": "Some risk #1" })
rRiskUid2 = client.createRecord('Risks', { "Name": "Some risk #2" })
rTaskUid = client.createRecord('Tasks', {
  "Name": "Test task with risk",
  "RiskIds": [rRiskUid1, rRiskUid2],
})
print('#5 ' + rUid)