# Fix Database Encoding (to UTF8)

In the old TYPO3 days other charsets/encodings than utf8 were used. 
Sometimes the encoding of the data is different that the encoding in the database definition. This causes some problems.

This tool convert all data and encoding definitions in the database to utf8. To be able to do so you need to know the current setup.

Variants:
1. the data stored in the database is encoded with an encoding different from utf8 (eg. latin1)
2. the data stored in the database is encoded in utf8 (because TYPO3 was configured with forceCharset to use utf8) 
   but the database encoding definition for tables and fields is set to xx encoding (non utf8, eg. latin1_swedish_ci)
3. some other weird setup

1\. and 2. can be fixed with this tool.

Fixing 1.
Let's say the data stored in the database is known to use the latin1 encoding. 

    typo3_fix_db_utf8.php -e latin1 -u db_username -p db_password -d database

Fixing 2. 

    typo3_fix_db_utf8.php -u db_username -p db_password -d database

WARNING: Make a backup of your database before using this tool. Messed up encodings can be tricky. This tool might make it even worse.

Use at your own risk!!

## Install

typo3_fix_db_utf8.php is a cli script. PHP has to be installed as cli.
    
You can run the script with php
    
    > php typo3_fix_db_utf8.php
        
or make the script executable:

    > chmod +x typo3_fix_db_utf8.php
    
and run it

    > ./typo3_fix_db_utf8.php
    
Then you may copy it ...

    > cp typo3_fix_db_utf8.php /usr/local/bin/typo3_fix_db_utf8
    
and just run it with 
    
    > typo3_fix_db_utf8
    
## Usage
    
Just call the script, it should give you hints how to use it.

## Remarks

I used this code over the years and lately I changed it to exist as a cli. It worked for me fine. But if it kills your cat, don't blame me. I warned you!

## Todo

There are several things that could be improved:

- add port, socket and host options
- add more encoding transformations for weird setups
- ...

I say: just hack this script to your needs. If that results in something useful for others, send a pull request. 

## Contribute

- Send pull requests to the repository. <https://github.com/colorcube/typo3_fix_db_utf8>
- Use the issue tracker for feedback and discussions. <https://github.com/colorcube/typo3_fix_db_utf8/issues>