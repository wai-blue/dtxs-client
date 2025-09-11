rem usage: run_use_case.bat <useCaseNumber> <configFile> <dbName>
rem example: run_use_case.bat 1_2 config.local.json test-db
"C:\Users\Arthur Dent 42\AppData\Local\Programs\Python\Python311\python.exe" -m tests.use_case_%1.py %2 %3