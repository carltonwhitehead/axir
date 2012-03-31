#!/bin/sh
touch AxIr.sqlite.db
rm AxIr.sqlite.db
sqlite3 AxIr.sqlite.db < schema.sql
chmod o+w AxIr.sqlite.db
