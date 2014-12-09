#!/bin/bash
# $Id: index_file.sh 17 2008-06-20 14:40:13Z wayne.graham $
#
# Bash script to start the import of a binary marc file for Solr indexing.

if [ "$1" = "" ]
then
    echo "Aufruf mit import-marc-solr4.sh <mrc-Datei> [optional: <solrcore> <alternative import.propertie-datei>]"
    echo "Breche ab."
    exit 1
fi

VUFIND_HOME_SOLR4="/usr/local/vufind2"
INDEX_OPTIONS='-d64 -Xms4096m -Xmx4096m -XX:+UseParallelGC -XX:+AggressiveOpts'
JAVA="java"
JAR_FILE="$VUFIND_HOME_SOLR4/import/SolrMarc.jar"

SOLR4_HOME="$VUFIND_HOME_SOLR4/solr"
SOLR4MARC_HOME="$VUFIND_HOME_SOLR4/import"
SOLR4_JAR_DEF="-Dsolrmarc.solr.war.path=$VUFIND_HOME_SOLR4/solr/jetty/webapps/solr.war"

export $VUFIND_HOME_SOLR4

if [ "$2" = "" ]
then
    SOLR4CORE="biblio"
else
    SOLR4CORE=$2
fi

if [ "$3" = "" ]
then
    PROPERTIES_FILE="$VUFIND_HOME_SOLR4/import/import.properties"
else
    PROPERTIES_FILE=$3
fi

ulimit -n 65535 

pushd $SOLR4_HOME
RUN_CMD="$JAVA $INDEX_OPTIONS $SOLR4_JAR_DEF -Dsolr.core.name=$SOLR4CORE -Dsolrmarc.path=$SOLR4MARC_HOME -Dsolr.path=$SOLR4_HOME -Dsolr.solr.home=$SOLR4_HOME $EXTRA_SOLRMARC_SETTINGS -jar $JAR_FILE $PROPERTIES_FILE $1"

echo "Now Importing $1 ..."
$RUN_CMD
popd

exit 0
