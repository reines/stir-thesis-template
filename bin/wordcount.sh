#!/bin/bash

perl bin/texcount.pl -relaxed -inc -nosub -nosum -total -q thesis.tex | grep -i "Words in text" | awk '{print $4}'
