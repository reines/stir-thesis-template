#!/bin/bash

OUTPUT_DIR="data"
INPUT_DIR="."

BIN_DIR=`pwd`
DATE=`date +%F`

cd ".."

if [ ! -f "${INPUT_DIR}/thesis.tex" -o ! -f "${INPUT_DIR}/thesis.pdf" ]
then
	echo "No input file"
	exit 1
fi

if [ ! -d "${OUTPUT_DIR}" ]
then
	mkdir "${OUTPUT_DIR}"
	exit 1
fi

FREQUENCIES_DIR="${OUTPUT_DIR}/frequencies-${DATE}"

if [ -d "${FREQUENCIES_DIR}" ]
then
	echo "Data already exists for today"
	exit 1
fi

mkdir "${FREQUENCIES_DIR}"

${BIN_DIR}/texcount.pl -relaxed -q -inc -incbib -template="{T},{1},{4},{5}," "${INPUT_DIR}/thesis.tex" >> "${OUTPUT_DIR}/wordcount.csv"

${BIN_DIR}/texcount.pl -restricted -freqSummary -nosub -nosum -merge -q -template="{T}" "${INPUT_DIR}/thesis.tex" >> "${OUTPUT_DIR}/uniquewords.csv"

${BIN_DIR}/texcount.pl -restricted -freq=5 -nosub -nosum -merge -q -template="{T}" "${INPUT_DIR}/thesis.tex" > "${FREQUENCIES_DIR}/thesis.txt"

for CHAPTER_DIR in ${INPUT_DIR}/content/chapter*
do
	CHAPTER=`basename "${CHAPTER_DIR}"`
	${BIN_DIR}/texcount.pl -restricted -freq -nosub -nosum -merge -q -template="{T}" "${INPUT_DIR}/content/$CHAPTER/*.tex" > "${FREQUENCIES_DIR}/${CHAPTER}.txt"
done

${BIN_DIR}/texcount.pl -relaxed -inc -nosub -nosum -printThesisState -total -brief -q "${INPUT_DIR}/thesis.tex" > "${OUTPUT_DIR}/status.csv"

PAGES=`pdftk "thesis.pdf" dump_data | grep -i "NumberOfPages" | awk '{print $2}'`
echo "${DATE//-//},${PAGES}" >> "${OUTPUT_DIR}/pagecounts.csv"
