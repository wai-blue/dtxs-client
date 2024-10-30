// ****************************************************************************
// Generates a JSON Schema from the YAML created by Dusan Daniska
// Author: Mikel Salazar
// ****************************************************************************

// ----------------------------------------------------------- GLOBAL VARIABLES

// To handle the data I/O
let fs = require("fs"), 
	text = {encoding: "utf8"},
	folderPath = __dirname + "\\",
	inputFileName = "../api-classes.yml",
	outputFileName = "../render/dtxs.schema.json";

// Global variables to handle the parsing process
let fileData = "",
	parsePosition = 0,
	classList = [],
	ExternalNamespace = "External";

// The initial structure of the JSON  Schema
let jsonSchema = {
	$schema: "http://json-schema.org/draft-07/schema",
	$comment: "The SONDIX taxonomy bundled into a JSON Schema file.",
	definitions: {},
	properties: {}
};

// ---------------------------------------------------------------- ENTRY POINT

// Show a message on console to indicate teh start of the process
console.log("GENERATING JSON SCHEMA... ");

// Read the HTML file
console.log("\tReading YAML file: " + inputFileName);
fileData = fs.readFileSync(folderPath + inputFileName, text);
let fileLines = fileData.split("\n");

let classNameParts = [], lastLevel = -1, 
	parseStates = ["classDefinition"], parseState = parseStates[0];
let classStructure = [], currentClass, lineIndex = 0, currentProperty;
for(let fileLine of fileLines) {

	// Trim both ends of the line
	let line = fileLine.trim(); lineIndex++;
	
	// Skip blank lines and comments
	if (line.length == 0 || line.startsWith("#")) continue;

	// Get the current level  from the characters at the beginning of the line
	let level = 0;
	for (let c of fileLine) { if (c == ' ') level+=0.5; else break; }
	let levelJump = level - lastLevel; lastLevel = level;
	while (level < parseStates.length - 1) { 
		parseStates.pop(); levelJump++; 
		parseState = parseStates[parseStates.length - 1]
		if (parseState == "classDefinition") {
			classNameParts.pop(); classStructure.pop();
			currentClass = classStructure[classStructure.length - 1];
		}
	}

	// Extract the key and value from the line
	let lineParts = line.split(":"), key = null , value = null;
	if (lineParts.length > 0) key = lineParts[0].trim();
	if (lineParts.length > 1) value = lineParts[1].trim();
	
	// Do different things depending on the
	parseState = parseStates[parseStates.length - 1];
	switch (parseState){
		case "classDefinition": 
			if (key == "$defs") key = ExternalNamespace;
			classNameParts.push(key);
			let className = classNameParts.join(".");
			currentClass = { name: className, title: key, properties: {} };
			classList.push(currentClass);
			classStructure.push(currentClass);
			parseStates.push("classStructure");
			console.log("Creating class:" + currentClass.title)
			break;
		case "classStructure":
			switch(key) {
				case "Description": 
					if (value == "|") parseStates.push("classDescription");
					else currentClass.description = value;
				break;
				case "Properties": 
					parseStates.push("propertyDefinition");
				break;
				case "_sub": 
					parseStates.push("classDefinition");
				break;
				default:
					if (levelJump > 0) parseStates.push("other");
				break;
			}
			break;
		case "classDescription": 
			if (!currentClass.description) currentClass.description = line;
			else currentClass.description += "\n" + line; 
		break;
		case "propertyDefinition": 
				currentProperty = currentClass.properties[key] = { name: key };
				parseStates.push("propertyStructure");
			break;
		case "propertyStructure": 
			switch(key) {
				case "Description": 
					if (value == "|") parseStates.push("propertyDescription");
					else currentProperty.description = value;
				break;
				case "Type": currentProperty.type = value; break;
				case "ReferencedClass":
					// currentProperty.ref = value;
					try { // If it is an array
						let data = JSON.parse(value)
						if(typeof data == "array") currentProperty.ref = data[0];
					} catch(e) {}
				break;
				case "Def": case "Definition":
					currentProperty.ref = value;
				break;
				case "Unit": currentProperty.unit = value.replace(/"/g, "");
				break;
				case "MinValue": currentProperty.minValue = value; break;
				case "MaxValue": currentProperty.maxValue = value; break;
				default: if (levelJump > 0) parseStates.push("other"); break;
			}
			break;
		case "propertyDescription": 
			if (!currentProperty.description) currentProperty.description = line;
			else currentProperty.description += "\n" + line;
			break;
		default:
			if (levelJump > 0) parseStates.push("other");
		break;
	}
}




// Set the properties of the root object to enable writing JSON files directly
for (let c of classList) {
	
	let definition = jsonSchema.definitions[c.name] = {
		title: c.title, description: c.description, properties: {}
	}

	for (let propertyID in c.properties) {
		if (propertyID.replace(/\{|\}|\,|\[|\]/g,"").length == 0) continue;
		let property = c.properties[propertyID];
		let p =	definition.properties[propertyID] = {};

		//  Add the description
		if (property.description) p.description = property.description;

		// dd the property name
		if (property.type) {
			// Now, that we have all the elements we need, we parse the value
			let t = property.type.toLowerCase();
			if (t == "") p.type = undefined;
			else if (t.includes("no type")) p.type = undefined;
			else if (t.includes("reference")) p.type = undefined;
			else if (t.startsWith("def") || property.ref) p.type = undefined;
			else if (t.startsWith("array")) p.type = "array";
			else if (t.includes("date")) p.type = "object";
			else switch (t) {
				case "boolean": p.type = "boolean"; break;
				case "number": p.type = "number"; break;
				case "decimal": p.type = "integer"; break;
				case "string": p.type = "string"; break;
				case "array": p.type = "array"; break;
				case "object": p.type = "object"; break;
				default: 
					console.log("\t\t\tUnknown type for property '" 
						+ propertyID + "': '" + property.type + "'" );
					p.type = "string";
			}

		}

		// Add the references to other 
		if (property.ref) {
			let ref = "#/definitions/" + 
				property.ref.replace(/\$defs?/g, ExternalNamespace); 
			if (p.type == "array") p.items = { $ref: ref };
			else p.$ref = ref;
		}

		// Min and maximum values
		if (property.minValue) p.min = property.minValue;
		if (property.maxValue) p.max = property.maxValue;

		// Properties that are not part of the JSON schema
		if (property.unit) p.unit = property.unit;
	}

	// Create the global lists
	let globalList = jsonSchema.properties[c.title] = {
		description: "The global list of " + c.title + " instances.",
		type: "array",
		items: { $ref: "#/definitions/" + c.name}
	} 
}

// Write the resulting JSON schema file
console.log("\tWriting JSON SCHEMA file: " + outputFileName);
let jsonSchemaData = JSON.stringify(jsonSchema, null, "\t");
jsonSchemaData = jsonSchemaData.replace(/\"items\"\: \{\n\t*(.*)\n\t*}/g, 
	"\"items\"\: \{ $1 }"); // Reduce the number of new lines
fs.writeFileSync(folderPath + outputFileName, jsonSchemaData, text);

// Show a message on console to indicate that the task has ended successfully
console.log("ALL DONE"); process.exit(0);