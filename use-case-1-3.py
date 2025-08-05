# This script executes activities described in
# use case #1 in DTXS documentaion on
# http://localhost/wai_blue/docs/dtxs-digital-twin-data-exchange-standard/api/use-cases/task-planning-risk-assessment-output-analysis

import os
import sys
import json
from clients.python.dtxs_client.main import DtxsClient

def prBlue(skk): print("\033[34m{}\033[00m" .format(skk))
def prRed(skk): print("\033[91m{}\033[00m" .format(skk))
def prGreen(skk): print("\033[92m{}\033[00m" .format(skk))
def prOrange(skk): print("\033[33m{}\033[00m" .format(skk))
def prYellow(skk): print("\033[93m{}\033[00m" .format(skk))
def prPurple(skk): print("\033[95m{}\033[00m" .format(skk))
def prCyan(skk): print("\033[96m{}\033[00m" .format(skk))
def prBlack(skk): print("\033[98m{}\033[00m" .format(skk))
def prLightGray(skk): print("\033[97m{}\033[00m" .format(skk))
def prLightBlue(skk): print("\033[94m{}\033[00m" .format(skk))
def prLightPurple(skk): print("\033[94m{}\033[00m" .format(skk))

prYellow("DTXS use case #1 test script")

if (len(sys.argv) <= 2):
  prYellow("Usage: python use-case-1-3.py <configFile> <dbName> <ifcModel>")
  prYellow("")
  prYellow("  configFile     Configuration of OAuth and DTXS endpoints")
  prYellow("  dbName         Name of the database where documents and records will be stored")
  sys.exit()

# Step 1: Prepare the environment

## Read this http://localhost/wai_blue/docs/dtxs-digital-twin-data-exchange-standard/api/use-cases/task-planning-risk-assessment-output-analysis/1-prepare-the-environment
## and check if you have your environment ready.

## Name of the database to be used will be provided as an argument

configFile = sys.argv[1]
dbName = sys.argv[2]

with open(configFile) as f: config = json.load(f)

prBlue("[1] Checking the environment")

client = DtxsClient(config['dtxsClient'])
client.getAccessToken()

prLightBlue("  [1.1] Authenticating")

if (len(client.accessToken) == 0):
  prRed("    !! Did not receive access token. Exitting.")
  sys.exit()

prGreen("    -> Received access token, length: " + str(len(client.accessToken)) + " bytes")

prLightBlue("  [1.2] Configuring DTXS client")

client.database = dbName
prGreen("    -> DTXS client configured")

prLightBlue("[3] Identify risks")
# prLightBlue("  [3.1] Download the 3D model")
# downloadedIfcFile = client.downloadDocument("root", ifcModelDocumentUid)
# with open("sample-3d-model.ifc" , "w") as f:
#   f.write(downloadedIfcFile)
# prGreen("    Document " + ifcModelDocumentUid + " downloaded as sample-3d-model.ifc")

prLightBlue("  [3.2] Download definition of the task")
prLightBlue("    Searching for the task by the 'name' property...")

search = client.searchRecords('[{"property": "content", "path": "$.Name", "match": "Remote measurement"}]')
foundTasks = json.loads(search)
foundTask = foundTasks[0]
foundTaskUID = foundTask["uid"]
teamUID = foundTask["content"]["TeamId"]

taskDefinition = client.getRecord(foundTaskUID)
with open("task_definition.json" , "w") as f:
  f.write(taskDefinition)
prGreen("    Found record " + foundTaskUID + " downloaded as task_definition.json")

teamDefinition = client.getRecord(teamUID)
teamDefinitionJson = json.loads(teamDefinition)
with open("team_definition.json" , "w") as f:
  f.write(teamDefinition)
prGreen("    Team record " + teamUID + " downloaded as team_definition.json")

teamMembers = teamDefinitionJson["content"]["MemberIds"]
memberIndex = 1
memberList = []
roleList = []
for member in teamMembers:
  foundMember = client.getRecord(member["MemberId"])
  memberList.append(foundMember)

  with open("member_"+ str(memberIndex) + ".json" , "w") as f:
    f.write(foundMember)
  prGreen("    Member " + member["MemberId"] + " downloaded as member_"+ str(memberIndex) + ".json")

  roleId = member.get("RoleId")
  if roleId is not None:
    foundRole = client.getRecord(roleId)
    roleList.append(foundRole)
    with open("role_"+ str(memberIndex) + ".json" , "w") as f:
      f.write(foundMember)
    prGreen("    Role " + roleId + " downloaded as role_"+ str(memberIndex) + ".json")

  memberIndex += 1

otherFiles = []
prLightBlue("  [3.3] Download other necessary data")
inputedId = "x"
while inputedId != "":

  prYellow("Please enter the UID of a record you wish to download (leave empty to stop)")
  inputedId = input()
  if (inputedId == ""):
    break
  record = client.getRecord(inputedId)
  if ('error' in record):
    sys.exit("ERROR: " + json.loads(record)["error"])
  otherFiles.append(record)

  prYellow("Please enter the name of the downloaded file")
  fileNameInput = input()
  with open(fileNameInput+".json" , "w") as f:
    f.write(record)
  prGreen("    Record " + inputedId + " downloaded as "+fileNameInput+".json")


prLightBlue("  Summary of downloaded data:")
print(" UID = " + foundTask['uid']
      + " | Class = " + foundTask['class']
      + " | Content = " + str(foundTask['content'])[:35] + "...")
print(" UID = " + teamDefinitionJson['uid']
      + " | Class = " + teamDefinitionJson['class']
      + " | Content = " + str(teamDefinitionJson['content'])[:35] + "...")
for member in memberList:
  memberJson = json.loads(member)
  print(" UID = " + memberJson['uid']
      + " | Class = " + memberJson['class']
      + " | Content = " + str(memberJson['content'])[:35] + "...")
for role in roleList:
  roleJson = json.loads(role)
  print(" UID = " + roleJson['uid']
      + " | Class = " + roleJson['class']
      + " | Content = " + str(roleJson['content'])[:35] + "...")
for other in otherFiles:
  otherJson = json.loads(other)
  print(" UID = " + otherJson['uid']
      + " | Class = " + otherJson['class']
      + " | Content = " + str(otherJson['content'])[:35] + "...")

prLightBlue("  [3.6] Upload risks back to the server and assign them to the task.")
risksUids = []
riskName = "x"
while riskName != "":
  prYellow("Enter the name of the found risk (leave empty to stop):")
  riskName = input()
  if (riskName == ""):
    break
  prYellow("Enter the description of the found risk:")
  description = input()
  prYellow("Enter the type of the found risk:")
  type = input()
  prYellow("Enter the severity of the found risk (1-9):")
  severity = int(input())
  prYellow("Enter the probability of the found risk (1-9):")
  probability = int(input())
  risksUid = client.createRecord("ndc:Safety.Risks.Register" , {
    "Name": riskName,
    "Description": description,
    "Type": type,
    "Severity": severity,
    "Probability": probability
  })
  prGreen("    -> risksUid = " + risksUid)
  risksUids.append(risksUid)

foundTask["content"]["RiskIds"] = risksUids
if (len(risksUids) > 0):
  client.updateRecord(foundTaskUID, foundTask["class"], foundTask["content"])
  prGreen("    -> Task updated with new risks")

prLightBlue("  All the tasks from step 3 have been completed!")