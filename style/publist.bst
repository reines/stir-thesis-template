%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%
% PUBLIST.BST		version 1.01	03-08-05
%
% v1.00: 	first release
% v1.01: 	"others" -> "\bgroup\scshape others\egroup"
%		(thanks to Mathias Schulze for pointing out that bug)
%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%
% This style extracts your list of publications from your .bib file. It is
% based on the plain.bst style file written by Oren Patashnik.
%
% In order to do so, you have to define your name and several possible 
% options in a @config entry, which should also be included.
%
%
% Remarks, comments, suggestions are welcome : markey@lsv.ens-cachan.fr
%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%
%  TODO
%
%  Tell me!
%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

ENTRY
  { address
    author
    booktitle
    chapter
    edition
    editor
    howpublished
    institution
    journal
    key
    month
    note
    number
    organization
    pages
    publisher
    school
    series
    title
    type
    volume
    year

    url			%% extra field, but requires loading url.sty.

    myname		%% Special @config field containing your name.
    jointwork		%% What will be written before the co-authors' names.
    nameformat		%% 0 = default = {ff }{vv~}{ll}{, jj}
			%% 1 =		 {f. }{vv~}{ll}{, jj}
			%% 2 = 		 {vv~}{ll}{, jj}{, ff}
			%% 3 =		 {vv~}{ll}{, jj}{, f.}
    testlevel		%% 0 = default = tight, 1 = medium, 2 = loose
    no-url		%% don't print URLs if not empty$
    reverse-sort	%% 0 = default = increasing date, otherwise decreasing date
  }
  { notauthor }
  { label }

STRINGS{your.name joint.work}
INTEGERS{name.format test.level url.used nourl reverse a b select total}

FUNCTION {not}
{   { #0 }
    { #1 }
  if$
}

FUNCTION {and}
{   'skip$
    { pop$ #0 }
  if$
}

FUNCTION {or}
{   { pop$ #1 }
    'skip$
  if$
}


FUNCTION{config}
{
  reverse-sort duplicate$ empty$
    {pop$ #0 'reverse :=}
    {chr.to.int$ #48 - 'reverse :=}
  if$

  jointwork duplicate$ empty$
    {"Joint work with " 'joint.work := pop$}
    {" " * 'joint.work :=}
  if$

  nameformat duplicate$ empty$
    {pop$ #0 'name.format :=}
    {chr.to.int$ #48 - 'name.format :=}
  if$ 

  testlevel duplicate$ empty$
    {pop$ #0 'test.level :=}
    {chr.to.int$ #48 - 'test.level :=}
  if$

  test.level duplicate$ #0 < swap$ #2 > or 
    {#0 'test.level :=}
    'skip$
  if$

  myname duplicate$ empty$ 
    {"I don't know who you are. Please tell me your name in a @config entry" warning$ pop$}
    {
     #1 
     test.level #0 = {"{vv~}{ll}{,~jj}{,~ff}"}{} if$
     test.level #1 = {"{vv~}{ll}{,~f.}"}{} if$
     test.level #2 = {"{vv~}{ll}"}{} if$
     format.name$ purify$
     duplicate$ " -- Extracting entries for " swap$ * " -- " * top$
     "l" change.case$ 'your.name :=}
  if$

  no-url duplicate$ empty$ 
    {#0 'nourl := pop$}
    {duplicate$ "0" =
       {#0 'nourl := pop$}
       {#1 'nourl := pop$}
    if$}
  if$

  #0 'url.used :=
  #0 'select :=
  #0 'total :=
}

FUNCTION{yourname}
{
your.name
}

INTEGERS { output.state before.all mid.sentence after.sentence after.block }

FUNCTION {init.state.consts}
{ #0 'before.all :=
  #1 'mid.sentence :=
  #2 'after.sentence :=
  #3 'after.block :=
}

STRINGS { s t }

FUNCTION {output.nonnull}
{ 's :=
  output.state mid.sentence =
    { ", " * write$ }
    { output.state after.block =
	{ add.period$ write$
	  newline$
	  "\newblock " write$
	}
	{ output.state before.all =
	    'write$
	    { add.period$ " " * write$ }
	  if$
	}
      if$
      mid.sentence 'output.state :=
    }
  if$
  s
}

FUNCTION {output}
{ duplicate$ empty$
    'pop$
    'output.nonnull
  if$
}

FUNCTION {output.check}
{ 't :=
  duplicate$ empty$
    { pop$ "empty " t * " in " * cite$ * warning$ }
    'output.nonnull
  if$
}

FUNCTION {output.bibitem}
{ newline$
  "\bibitem{" write$
  cite$ write$
  "}" write$
  newline$
  ""
  before.all 'output.state :=
}

FUNCTION {fin.entry}
{ add.period$
  write$
  newline$
}

FUNCTION {new.block}
{ output.state before.all =
    'skip$
    { after.block 'output.state := }
  if$
}

FUNCTION {new.sentence}
{ output.state after.block =
    'skip$
    { output.state before.all =
	'skip$
	{ after.sentence 'output.state := }
      if$
    }
  if$
}


FUNCTION {mult}
{
 'a :=                          %% on memorise la premiere valeur
 'b :=                          %% on memorise la deuxieme valeur

 b #0 <                         %% L'idee ici est de mettre le signe
    {#0 #1 - #0 b - 'b :=}      %% de b sur la pile, et de prendre 
    {#1}                        %% la valeur absolue de b.
 if$                            %%

 #0                             %% 
 {b #0 >}                       %% tant que b est strictement positif, 
 {                              %% on ajoute a au premier element de 
   a +                          %% la pile, et on decremente b. A la
   b #1 - 'b :=                 %% fin, on a bien le produit de a 
 }                              %% par b.
 while$                         %%

 swap$ #1 =                     %% Enfin, on corrige le signe, si 
   'skip$                       %% besoin, en testant le signe de b
   {#0 swap$ -}                 %% que l'on avait mis sur la pile au 
 if$                            %% depart.
}




FUNCTION {chr.to.value}         %% D'abord, la valeur d'un caractere.
{
  chr.to.int$ #48 -             %% en ASCII, "0" -> 48
  duplicate$ duplicate$         %%           "1" -> 49
  #0 < swap$ #9 > or            %%              ...
    {                           %%           "9" -> 57
      #48 + int.to.chr$ 
      " n'est pas un nombre..." * 
      warning$                  %% Si c'est pas un chiffre,
      pop$ #0                   %% on retourne 0...
    }
  {}
  if$
}

FUNCTION {str.to.int.aux}       %% La fonction auxiliaire.
{
  {duplicate$ empty$ not}       %% Tant que la chaine n'est pas vide, 
    {                           %% on prend son premier chiffre
      swap$ #10 mult 'a :=      %% et on le met comme il faut...
      duplicate$ #1 #1 substring$ 
      chr.to.value a +
      swap$
      #2 global.max$ substring$
    }
  while$
  pop$
}

FUNCTION {str.to.int}
{                               %% On regarde si le nombre est negatif
  duplicate$ #1 #1 substring$ "-" = 
    {#1 swap$ #2 global.max$ substring$}
    {#0 swap$}
  if$
                                %% On initialise, puis on appelle
  #0 swap$ str.to.int.aux       %% la fonction auxiliaire.
  swap$ 
    {#0 swap$ -}                %% On met le signe comme il faut.
    {}
  if$
}

FUNCTION {new.block.checka}
{ empty$
    'skip$
    'new.block
  if$
}

FUNCTION {new.block.checkb}
{ empty$
  swap$ empty$
  and
    'skip$
    'new.block
  if$
}

FUNCTION {new.sentence.checka}
{ empty$
    'skip$
    'new.sentence
  if$
}

FUNCTION {new.sentence.checkb}
{ empty$
  swap$ empty$
  and
    'skip$
    'new.sentence
  if$
}

FUNCTION {field.or.null}
{ duplicate$ empty$
    { pop$ "" }
    'skip$
  if$
}

FUNCTION {emphasize}
{ duplicate$ empty$
    { pop$ "" }
    { "{\em " swap$ * "}" * }
  if$
}

INTEGERS { nameptr namesleft numnames removed localptr }


FUNCTION{test}
{
'localptr :=
s localptr "{vv }{ll}{, jj}{, ff}" format.name$ purify$ "l" change.case$ yourname = 
test.level #1 =
s localptr "{vv }{ll}{, f.}" format.name$ purify$ "l" change.case$ yourname = and
test.level #2 =
s localptr "{vv }{ll}" format.name$ purify$ "l" change.case$ yourname = and
or or
}

FUNCTION{isauthor}
{
  #1 'notauthor :=
  total #1 + 'total :=

  author duplicate$ empty$
    {pop$ ""}
    {}
  if$
  's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
    { namesleft #0 > }
    { nameptr test  
	{#0 'notauthor := #0 'namesleft := select #1 + 'select :=}
	{}
      if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    }
  while$
}

FUNCTION {format.coauthor.names}
{ 's :=
  #1 'nameptr :=
  #0 'removed :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  namesleft #1 =
    {#1 test   
	{#1 'removed :=}
	{}
    if$ ""
    }
    {
    { namesleft #0 > }
    { 
  nameptr test  
  {#1 'removed :=}     %% nothing to write
  {s nameptr 
  name.format duplicate$ #2 <
	{#0 = {"{ff }{vv~}{ll}{,~jj}"} {"{f.~}{vv~}{ll}{, jj}"} if$ }
	{#2 = {"{vv~}{ll}{,~jj}{, ff}"} {"{vv~}{ll}{,~jj}{,~f.}"} if$}
  if$
  format.name$ 't :=
    nameptr #1 >  removed #0 = and nameptr #2 > or
	{ namesleft #2 > namesleft #2 = removed #1 = and or 
	    { ", " * t * }
	    {  t "others" =
		{ " et~al." * }
		{ " and " * t * }
	      if$
	    }
	  if$
	}
	't
      if$
   }
if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    }
  while$
  joint.work swap$ *
  }
  if$  

  %% Last, we test if you are an author...
  %% This should never occur since we tested for that before...
  removed #0 = {"I found a bug. Please report it to markey@lsv.ens-cachan.fr" warning$ 
	"with the complete entry for " cite$ * warning$} {} if$
}

FUNCTION {format.names}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
    s nameptr "{vv~}\bgroup\scshape {ll}\egroup{, ff}{, jj}" format.name$ 't :=
      nameptr #1 >
        { namesleft #1 >
            { ", " * t * }
            { numnames #2 >
                { "," * }
                'skip$
              if$
              t "\bgroup\scshape others\egroup" =
                { " et~al." * }
                { " and " * t * }
              if$
            }
          if$
        }
        't
      if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    { namesleft #0 > }
    { s nameptr "{ff~}{vv~}\bgroup\scshape {ll}\egroup{, jj}" format.name$ 't :=
      nameptr #1 >
        { namesleft #1 >
            { ", " * t * }
            { numnames #2 >
                { "," * }
                'skip$
              if$
              t "\bgroup\scshape others\egroup" =
                { " et~al." * }
                { " and " * t * }
              if$
            }
          if$
        }
        't
      if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    }
  while$
}


FUNCTION {format.coauthors}
{ author empty$
    { "" }
    { author format.coauthor.names }
  if$
}

FUNCTION {format.authors}
{ author empty$
    { "" }
    { author format.names }
  if$
}


FUNCTION {format.editors}
{ editor empty$
    { "" }
    { editor format.names
      editor num.names$ #1 >
	{ ", editors" * }
	{ ", editor" * }
      if$
    }
  if$
}

FUNCTION {format.title}
{ title empty$
    { "" }
    { title "t" change.case$ }
  if$
}

FUNCTION {n.dashify}
{ 't :=
  ""
    { t empty$ not }
    { t #1 #1 substring$ "-" =
	{ t #1 #2 substring$ "--" = not
	    { "--" *
	      t #2 global.max$ substring$ 't :=
	    }
	    {   { t #1 #1 substring$ "-" = }
		{ "-" *
		  t #2 global.max$ substring$ 't :=
		}
	      while$
	    }
	  if$
	}
	{ t #1 #1 substring$ *
	  t #2 global.max$ substring$ 't :=
	}
      if$
    }
  while$
}

FUNCTION {format.date}
{ year empty$
    { month empty$
	{ "" }
	{ "there's a month but no year in " cite$ * warning$
	  month
	}
      if$
    }
    { month empty$
	'year
	{ month " " * year * }
      if$
    }
  if$
}

FUNCTION {format.btitle}
{ title emphasize
}

FUNCTION {tie.or.space.connect}
{ duplicate$ text.length$ #3 <
    { "~" }
    { " " }
  if$
  swap$ * *
}

FUNCTION {either.or.check}
{ empty$
    'pop$
    { "can't use both " swap$ * " fields in " * cite$ * warning$ }
  if$
}

FUNCTION {format.bvolume}
{ volume empty$
    { "" }
    { "volume" volume tie.or.space.connect
      series empty$
	'skip$
	{ " of " * series emphasize * }
      if$
      "volume and number" number either.or.check
    }
  if$
}

FUNCTION {format.number.series}
{ volume empty$
    { number empty$
	{ series field.or.null }
	{ output.state mid.sentence =
	    { "number" }
	    { "Number" }
	  if$
	  number tie.or.space.connect
	  series empty$
	    { "there's a number but no series in " cite$ * warning$ }
	    { " in " * series * }
	  if$
	}
      if$
    }
    { "" }
  if$
}

FUNCTION {format.edition}
{ edition empty$
    { "" }
    { output.state mid.sentence =
	{ edition "l" change.case$ " edition" * }
	{ edition "t" change.case$ " edition" * }
      if$
    }
  if$
}

INTEGERS { multiresult }

FUNCTION {multi.page.check}
{ 't :=
  #0 'multiresult :=
    { multiresult not
      t empty$ not
      and
    }
    { t #1 #1 substring$
      duplicate$ "-" =
      swap$ duplicate$ "," =
      swap$ "+" =
      or or
	{ #1 'multiresult := }
	{ t #2 global.max$ substring$ 't := }
      if$
    }
  while$
  multiresult
}

FUNCTION {format.pages}
{ pages empty$
    { "" }
    { pages multi.page.check
	{ "pages" pages n.dashify tie.or.space.connect }
	{ "page" pages tie.or.space.connect }
      if$
    }
  if$
}

FUNCTION {format.vol.num.pages}
{ volume field.or.null
  number empty$
    'skip$
    { "(" number * ")" * *
      volume empty$
	{ "there's a number but no volume in " cite$ * warning$ }
	'skip$
      if$
    }
  if$
  pages empty$
    'skip$
    { duplicate$ empty$
	{ pop$ format.pages }
	{ ":" * pages n.dashify * }
      if$
    }
  if$
}

FUNCTION {format.chapter.pages}
{ chapter empty$
    'format.pages
    { type empty$
	{ "chapter" }
	{ type "l" change.case$ }
      if$
      chapter tie.or.space.connect
      pages empty$
	'skip$
	{ ", " * format.pages * }
      if$
    }
  if$
}

FUNCTION {format.in.ed.booktitle}
{ booktitle empty$
    { "" }
    { editor empty$
	{ "In " booktitle emphasize * }
	{ "In " format.editors * ", " * booktitle emphasize * }
      if$
    }
  if$
}

FUNCTION {empty.misc.check}
{ author empty$ title empty$ howpublished empty$
  month empty$ year empty$ note empty$
  and and and and and
  key empty$ not and
    { "all relevant fields are empty in " cite$ * warning$ }
    'skip$
  if$
}

FUNCTION {format.thesis.type}
{ type empty$
    'skip$
    { pop$
      type "t" change.case$
    }
  if$
}

FUNCTION {format.tr.number}
{ type empty$
    { "Technical Report" }
    'type
  if$
  number empty$
    { "t" change.case$ }
    { number tie.or.space.connect }
  if$
}

FUNCTION {format.article.crossref}
{ key empty$
    { journal empty$
	{ "need key or journal for " cite$ * " to crossref " * crossref *
	  warning$
	  ""
	}
	{ "In {\em " journal * "\/}" * }
      if$
    }
    { "In " key * }
  if$
  " \cite{" * crossref * "}" *
}

FUNCTION {format.crossref.editor}
{ editor #1 "{vv~}{ll}" format.name$
  editor num.names$ duplicate$
  #2 >
    { pop$ " et~al." * }
    { #2 <
	'skip$
	{ editor #2 "{ff }{vv }{ll}{ jj}" format.name$ "others" =
	    { " et~al." * }
	    { " and " * editor #2 "{vv~}{ll}" format.name$ * }
	  if$
	}
      if$
    }
  if$
}

FUNCTION {format.book.crossref}
{ volume empty$
    { "empty volume in " cite$ * "'s crossref of " * crossref * warning$
      "In "
    }
    { "Volume" volume tie.or.space.connect
      " of " *
    }
  if$
  editor empty$
  editor field.or.null author field.or.null =
  or
    { key empty$
	{ series empty$
	    { "need editor, key, or series for " cite$ * " to crossref " *
	      crossref * warning$
	      "" *
	    }
	    { "{\em " * series * "\/}" * }
	  if$
	}
	{ key * }
      if$
    }
    { format.crossref.editor * }
  if$
  " \cite{" * crossref * "}" *
}

FUNCTION {format.incoll.inproc.crossref}
{ editor empty$
  editor field.or.null author field.or.null =
  or
    { key empty$
	{ booktitle empty$
	    { "need editor, key, or booktitle for " cite$ * " to crossref " *
	      crossref * warning$
	      ""
	    }
	    { "In {\em " booktitle * "\/}" * }
	  if$
	}
	{ "In " key * }
      if$
    }
    { "In " format.crossref.editor * }
  if$
  " \cite{" * crossref * "}" *
}

FUNCTION{format.url}
{
url missing$ nourl #1 = or
  {""}
  {
  #0 url.used =
    {"You use the URL field. Don't forget to include the `url' package." Warning$}
    'skip$
  if$
  #1 'url.used :=
  url duplicate$ #1 #4 substring$ duplicate$ "http" = swap$ "ftp:" = or 
    'skip$
    {"http://" swap$ *}
  if$
  "\url{" swap$ "}" * * 
  }
if$
}


FUNCTION {article}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  crossref missing$
    { journal emphasize "journal" output.check
      format.vol.num.pages output
      format.date "year" output.check
    }
    { format.article.crossref output.nonnull
      format.pages output
    }
  if$
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {book}
{ output.bibitem
%  author empty$
%    { format.editors "author and editor" output.check }
%    { format.authors output.nonnull
%      crossref missing$
%	{ "author and editor" editor either.or.check }
%	'skip$
%      if$
%    }
%  if$
  new.block
  format.btitle "title" output.check
  crossref missing$
    { format.bvolume output
      new.block
      format.number.series output
      new.sentence
      publisher "publisher" output.check
      address output
    }
    { new.block
      format.book.crossref output.nonnull
    }
  if$
  format.edition output
  format.date "year" output.check
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {booklet}
{ output.bibitem
%  format.authors output
%  new.block
  format.title "title" output.check
  howpublished address new.block.checkb
  howpublished output
  address output
  format.date output
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {inbook}
{ output.bibitem
%  author empty$
%    { format.editors "author and editor" output.check }
%    { format.authors output.nonnull
%      crossref missing$
%	{ "author and editor" editor either.or.check }
%	'skip$
%      if$
%    }
%  if$
%  new.block
  format.btitle "title" output.check
  crossref missing$
    { format.bvolume output
      format.chapter.pages "chapter and pages" output.check
      new.block
      format.number.series output
      new.sentence
      publisher "publisher" output.check
      address output
    }
    { format.chapter.pages "chapter and pages" output.check
      new.block
      format.book.crossref output.nonnull
    }
  if$
  format.edition output
  format.date "year" output.check
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {incollection}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  crossref missing$
    { format.in.ed.booktitle "booktitle" output.check
      format.bvolume output
      format.number.series output
      format.chapter.pages output
      new.sentence
      publisher "publisher" output.check
      address output
      format.edition output
      format.date "year" output.check
    }
    { format.incoll.inproc.crossref output.nonnull
      format.chapter.pages output
    }
  if$
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {inproceedings}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  crossref missing$
    { format.in.ed.booktitle "booktitle" output.check
      format.bvolume output
      format.number.series output
      format.pages output
      address empty$
	{ organization publisher new.sentence.checkb
	  organization output
	  publisher output
	  format.date "year" output.check
	}
	{ address output.nonnull
	  format.date "year" output.check
	  new.sentence
	  organization output
	  publisher output
	}
      if$
    }
    { format.incoll.inproc.crossref output.nonnull
      format.pages output
    }
  if$
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {conference} { inproceedings }

FUNCTION {manual}
{ output.bibitem
  author empty$
    { organization empty$
	'skip$
	{ organization output.nonnull
	  address output
	}
      if$
    }
%    { format.authors output.nonnull }
  {}
  if$
  new.block
  format.btitle "title" output.check
  author empty$
    { organization empty$
	{ address new.block.checka
	  address output
	}
	'skip$
      if$
    }
    { organization address new.block.checkb
      organization output
      address output
    }
  if$
  format.edition output
  format.date output
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {mastersthesis}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  "Master's thesis" format.thesis.type output.nonnull
  school "school" output.check
  address output
  format.date "year" output.check
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {misc}
{ output.bibitem
%  format.authors output
  title howpublished new.block.checkb
  format.title output
  howpublished new.block.checka
  howpublished output
  format.date output
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
  empty.misc.check
}

FUNCTION {phdthesis}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.btitle "title" output.check
  new.block
  "PhD thesis" format.thesis.type output.nonnull
  school "school" output.check
  address output
  format.date "year" output.check
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {proceedings}
{ output.bibitem
  editor empty$
    { organization output }
%    { format.editors output.nonnull }
    {}
  if$
  new.block
  format.btitle "title" output.check
  format.bvolume output
  format.number.series output
  address empty$
    { editor empty$
	{ publisher new.sentence.checka }
	{ organization publisher new.sentence.checkb
	  organization output
	}
      if$
      publisher output
      format.date "year" output.check
    }
    { address output.nonnull
      format.date "year" output.check
      new.sentence
      editor empty$
	'skip$
	{ organization output }
      if$
      publisher output
    }
  if$
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {techreport}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  format.tr.number output.nonnull
  institution "institution" output.check
  address output
  format.date "year" output.check
  new.block
  format.url output
  new.block
  note output
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {unpublished}
{ output.bibitem
%  format.authors "author" output.check
%  new.block
  format.title "title" output.check
  new.block
  note "note" output.check
  format.date output
  format.url output
  new.block
  new.block
  format.coauthors output
  fin.entry
}

FUNCTION {default.type} { misc }

MACRO {jan} {"January"}

MACRO {feb} {"February"}

MACRO {mar} {"March"}

MACRO {apr} {"April"}

MACRO {may} {"May"}

MACRO {jun} {"June"}

MACRO {jul} {"July"}

MACRO {aug} {"August"}

MACRO {sep} {"September"}

MACRO {oct} {"October"}

MACRO {nov} {"November"}

MACRO {dec} {"December"}

MACRO {acmcs} {"ACM Computing Surveys"}

MACRO {acta} {"Acta Informatica"}

MACRO {cacm} {"Communications of the ACM"}

MACRO {ibmjrd} {"IBM Journal of Research and Development"}

MACRO {ibmsj} {"IBM Systems Journal"}

MACRO {ieeese} {"IEEE Transactions on Software Engineering"}

MACRO {ieeetc} {"IEEE Transactions on Computers"}

MACRO {ieeetcad}
 {"IEEE Transactions on Computer-Aided Design of Integrated Circuits"}

MACRO {ipl} {"Information Processing Letters"}

MACRO {jacm} {"Journal of the ACM"}

MACRO {jcss} {"Journal of Computer and System Sciences"}

MACRO {scp} {"Science of Computer Programming"}

MACRO {sicomp} {"SIAM Journal on Computing"}

MACRO {tocs} {"ACM Transactions on Computer Systems"}

MACRO {tods} {"ACM Transactions on Database Systems"}

MACRO {tog} {"ACM Transactions on Graphics"}

MACRO {toms} {"ACM Transactions on Mathematical Software"}

MACRO {toois} {"ACM Transactions on Office Information Systems"}

MACRO {toplas} {"ACM Transactions on Programming Languages and Systems"}

MACRO {tcs} {"Theoretical Computer Science"}

READ

FUNCTION {call.config}
{type$ "config" = 'call.type$ 'skip$ if$}

ITERATE{call.config}

ITERATE{isauthor}

FUNCTION {sortify}
{ purify$
  "l" change.case$
}

INTEGERS { len }

FUNCTION {chop.word}
{ 's :=
  'len :=
  s #1 len substring$ =
    { s len #1 + global.max$ substring$ }
    's
  if$
}

FUNCTION {sort.format.names}
{ 's :=
  #1 'nameptr :=
  ""
  s num.names$ 'numnames :=
  numnames 'namesleft :=
    { namesleft #0 > }
    { nameptr #1 >
	{ "   " * }
	'skip$
      if$
      s nameptr "{vv{ } }{ll{ }}{  ff{ }}{  jj{ }}" format.name$ 't :=
      nameptr numnames = t "others" = and
	{ "et al" * }
	{ t sortify * }
      if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    }
  while$
}

FUNCTION {sort.format.title}
{ 't :=
  "A " #2
    "An " #3
      "The " #4 t chop.word
    chop.word
  chop.word
  sortify
  #1 global.max$ substring$
}

FUNCTION {author.sort}
{ author empty$
    { key empty$
	{ "to sort, need author or key in " cite$ * warning$
	  ""
	}
	{ key sortify }
      if$
    }
    { author sort.format.names }
  if$
}

FUNCTION {author.editor.sort}
{ author empty$
    { editor empty$
	{ key empty$
	    { "to sort, need author, editor, or key in " cite$ * warning$
	      ""
	    }
	    { key sortify }
	  if$
	}
	{ editor sort.format.names }
      if$
    }
    { author sort.format.names }
  if$
}

FUNCTION {author.organization.sort}
{ author empty$
    { organization empty$
	{ key empty$
	    { "to sort, need author, organization, or key in " cite$ * warning$
	      ""
	    }
	    { key sortify }
	  if$
	}
	{ "The " #4 organization chop.word sortify }
      if$
    }
    { author sort.format.names }
  if$
}

FUNCTION {editor.organization.sort}
{ editor empty$
    { organization empty$
	{ key empty$
	    { "to sort, need editor, organization, or key in " cite$ * warning$
	      ""
	    }
	    { key sortify }
	  if$
	}
	{ "The " #4 organization chop.word sortify }
      if$
    }
    { editor sort.format.names }
  if$
}

FUNCTION {nan}
{
duplicate$ 
#1 #1 substring$ chr.to.int$ duplicate$ #58 < #47 > or
swap$ duplicate$ 
#2 #1 substring$ chr.to.int$ duplicate$ #58 < #47 > or 
swap$ duplicate$ 
#3 #1 substring$ chr.to.int$ duplicate$ #58 < #47 > or 
swap$ 
#4 #1 substring$ chr.to.int$ duplicate$ #58 < #47 > or 
or or or
}

FUNCTION {presort}
{ 
type$ "config" = notauthor #1 = or 
  {"" 'sort.key$ :=}
  {
%  type$ "book" =
%  type$ "inbook" =
%  or
%    'author.editor.sort
%    { type$ "proceedings" =
%	'editor.organization.sort
%	{ type$ "manual" =
%	    'author.organization.sort
%	    'author.sort
%	  if$
%	}
%      if$
%    }
%  if$
  "    "
%  *
  year duplicate$ empty$
	{"Entry " cite$ * " has no year, and will be placed at the end." * warning$ pop$ "9999"}
	{duplicate$ text.length$ duplicate$ #4 = not
	  {"Entry " cite$ * " has a weird year-field, and will be put at the end." * warning$ pop$ pop$ "9999"}
	  {#3 - #4 substring$ duplicate$ nan 
		{skip$}
		{"Entry " cite$ * " has an ambiguous year-field, and will be put at the end." * warning$ pop$ "9999"}
	  if$}
	if$}
  if$
  reverse 
	{str.to.int #9999 swap$ -}
	{str.to.int}
  if$
  duplicate$ duplicate$ #0 = swap$ #9999 = or
    {pop$ "9999"}
    {int.to.str$}
  if$
  *
  "    "
  *
  title field.or.null
  sort.format.title
  *
  #1 entry.max$ substring$
  'sort.key$ :=
  }
if$
}

ITERATE {presort}

SORT

STRINGS { longest.label }

INTEGERS { number.label longest.label.width }

FUNCTION {initialize.longest.label}
{ "" 'longest.label :=
  #1 'number.label :=
  #0 'longest.label.width :=
}

FUNCTION {longest.label.pass}
{ number.label int.to.str$ 'label :=
  number.label #1 + 'number.label :=
  label width$ longest.label.width >
    { label 'longest.label :=
      label width$ 'longest.label.width :=
    }
    'skip$
  if$
}

EXECUTE {initialize.longest.label}

ITERATE {longest.label.pass}

FUNCTION {begin.bib}
{ preamble$ empty$
    'skip$
    { preamble$ write$ newline$ }
  if$
  "\begin{thebibliography}{"  longest.label  * "}" * write$ newline$
}

EXECUTE {begin.bib}

EXECUTE {init.state.consts}

FUNCTION{call.other.types}
{type$ "l" change.case$ "config" = 
  'skip$ 
  {
  notauthor #1 = 
    'skip$
    'call.type$ 
  if$
  }
if$
}

ITERATE{call.other.types}

FUNCTION {end.bib}
{ newline$
  "\end{thebibliography}" write$ newline$
  " -- I selected " select int.to.str$ * " entries out of " * total int.to.str$ * ". -- " * top$
}

EXECUTE {end.bib}
