# Autocross Instant Results

A web app that displays competitive standings and run information from legacy versions of AXware.

## Features

- Detailed individual results with time and penalty
- Overall raw, pax, and class results
- Mobile-friendly design

## Requirements

- AXware 2013 or _older_
- SQLite 3
- HTTP server
- PHP 5.3 or higher
    - Modules enabled:
        - PDO
        - SQLite 3
    - Include path:
        - Zend Framework 1.12

## Installation

1. Place the project contents in a folder readable by the user of your HTTP server
2. Make sure the public/ folder of the project is reachable via an HTTP request (it's best not to expose the rest of the project structure to HTTP requests, just make sure the HTTP software on the server can read it)
3. Make sure the data/cache and data/db folder are writable by the HTTP software
4. Execute the shell script data/db/create-db.sh (or manually execute data/db/schema.sql against a new sqlite database at data/db/AxIr.sqlite.db)
5. Open the application/configs/application.ini and adjust the settings defined there (refer to in-line comments)

## Disclaimer

Autocross Instant Results is not in any way affiliated with AXware Systems.