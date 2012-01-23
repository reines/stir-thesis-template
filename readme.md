## Stirling University Insititude of Computing Science and Mathematics PhD Thesis Template

A latex thesis template designed for the Insititude of Computing Science and Mathematics at Stirling University - based on [A Classic Thesis Style](http://www.ctan.org/tex-archive/macros/latex/contrib/classicthesis/) (v2.9), by Andr'e Miede.

## Template Structure

## Getting Started

### Personalisation

- title etc
- colours
- title page

### Configuration

- drafting

### Parts

### TODO notes

TODO notes are provided using the [todonotes](http://www.ctan.org/tex-archive/macros/latex/contrib/todonotes/) package.

### Acronyms

Acronyms are provided using the [acronym](http://ctan.org/tex-archive/macros/latex/contrib/acronym) package.

They are defined in the file `content/beginningContent/acronyms.tex`, using the syntax:

    \acro{key}[short]{long}    	Define a new acronym.

When it comes to using an already defined acronym, the following syntax is used:

    \ac{key}					Display an already defined acronym.
    \acs{key}					Display only the short version of an acronym.
    \acl{key}					Display only the long version of an acronym.
    \acf{key}					Displays the full (long and short) version of an acronym.
    
    \ac[slf]p{key}				Same as \ac[slf], but displays the acronym in plural form.

Unfortunately the list of acronyms is not automatically sorted. You probably want to define them in alphabetical order.

### Own Publications

### References

### Images

## Compiling

## Data recording

## University Requirements

Found In the [SGRS Postgraduate Research Student Handbook](http://www.research.stir.ac.uk/documents/PGRHandbook2010-11FINALVERSION.pdf)(2010/11 version) and the [CS&M Research Student Guide](http://pgtips.cs.stir.ac.uk/sites/default/files/Research%20Student%20Guide-together-2009.pdf)(2009 version). The template should meet all the requirements, however they are described here for reference.

- Margins should be around 15mm, with a 40mm left margin for binding.
- Regular text should be 10pt using a Serif font.
- Double line spacing should be used.
- It must be printed single sided on A4 paper.

## General Writing Guidelines

- capitalisation (Chapter vs chapter)
- be consistent with internal references

