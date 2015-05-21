LiarLiar
========

Just make stuff up to populate a MySQL database


Design
======

The two hardest objectives to meet when generating a relational test data set are 
(1) making relations that actually relate to one another and
(2) making properties that fit the constraints of the abstract data types. 

The first objective can be met by caching named key values. A properly designed schema should embed enough
information to navigate the foreign keys, but most schemas for skunkworks apps are nowhere near properly designed.
DRY does not apply with these apps.  The objective can be met by explicitly defining the foreign key relations. 

Foreign keys say a little about self-consistency: if a table has a foreign key, one would normally expect 
that there exists at least one relation that is the authoritative record defining the key. There may 
be multiple relations that participate in the relation - probably not a good design choice but it is quite possible.
A declaratively annotated schema should make it feasible to determine the consistency. Stored procedures are of no help here.

The second objective is also impossible to meet given just the relational schema. Column values in SQL compliant
data stores are specified in terms of storage types (VARCHAR,TEXT, INT, DATE ...), not domain types or abstract notations.
So, for instance, a BLOB can contain a PNG notation value or a JPEG notation value, an INT can contain a dollar quantity, 
a length measurement, or a discrete number taken to be in 1:1 correspondance to an enumerated token value, or some other 
magnitude.  SQL data types say next to nothing about the encoding and interpretation of the semantic notation.

A SQL schema will have to be extended by annotating it with enough information to generate self-consistent 
non-vacuous values. Essentially, you are building a functor that generates functions that generate an 
interconnected graph of data.


Running
=======

Download

Run `composer install`

A simple command-line interface example is provided in `cli/`.
Edit the `cli/config.yml` to set the database connection information, 
then `cd ./cli/; php -f tryliar.php`.

The example renders the relations into raw SQL text. There are other renderers possible. 

Limitations
===========
The tool is still quite gruesome and needs some tender loving thinking: 

I did not do a good job of SOLIDifying the code when I threw it together. The tests are craptastic as well. Although it does generate relational data, the specification is crudely based on PHP syntax and could be much more readable and concise.

The example uses a built-in self-test ("bist"); bist is a quick-and-dirty throw-away means of doing a unit test. It should be thrown away.

Field format hints are not validated. In particular, if you use a hint that does not exist the tool will vomit.
