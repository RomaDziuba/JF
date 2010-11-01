#!/bin/bash

function create_dir {
    if [ ! -d $1 ]; then
        mkdir $1
    fi
    
    chmod -R 777 $1
}

create_dir tblDefs/
create_dir tblHandlers/
create_dir logs/
create_dir templates/compiled/