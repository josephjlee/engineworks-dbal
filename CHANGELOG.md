# version 1.5.0 2017-02-24
- Add `$overrideTypes` parameter for `DBAL::queryResult` and `DBAL::queryRecordset` methods.
  The driver Sqlite does not recognize properly the commontypes of fields
- Deprecate `DBAL::query` method
- Improve test, add Mysqli and environment configuration for mysql
- Use new validation rules of php-cs-fixer
- Do not build using hhvm, include php 7.1, add mysql
- Add compatibility on php 7.1 by fixing contructor on `EngineWorks\DBAL::Settings`

# version 1.4.1 2017-02-23
- There were some errors using the recordset on weird table and field names, this version make the following changes:
    - `sqlField(a, b) => a as "b"`: New method, only escape the alias
    - `sqlFieldEscape(a, b) => "a" as "b"`: New method, escape both, the name and the alias
    - `sqlTable(a, b) => "suffix_a" as "b"`: Not changed
    - `sqlTableEscape(a, b) => "a" as "b"`: Changed from protected to public
- Change Recordset to use this methods when building the sql sentences.

# version 1.3.1 2017-01-31
- Fix bug when sqlQuote receives a stringable object but it does not take value to parse it as int or float

# version 1.3.0  2016-11-14
- Add Mssql driver
- Allow pass entity and primary keys to Recordset
- Sqlite uses fetch behavior to reset the query
- Improve tests
- Add Travis-CI support
- Improve documentation

# version 1.2.3 2016-09-01
- Rename project to eclipxe/engineworks-dbal
- Move from gitlab to github
- Changes on README
- Introduce CoC, Contributing, TODO, LICENSE

# version 1.2.2 2016-08-31
- Small fix on docblock of DBAL::sqlConcatenate variadic method
- Small fix must not use numRows but resultCount()
- Small fix inpections and warnings reported by PhpStorm
- Rename ruleset.xml to phpcs.xml
- Increase coverage on Sqlite\Result

# version 1.2.1 2016-08-09

- Implement generic Iterators for Result and Recordset
- Result and Recordset now implements IteratorAggregate and Countable interfaces
- Fix moveTo on Sqlite/Result
- Test Sqlite/Result
- Improve code style

# version 1.1.3 2016-07-31

- Fix Pager with no query for count
- Add test for pager
- Add a sqlite database for testing
- Improve coding standards

# version 1.1.2 2016-07-25

- Move sqlLimit to a trait
- Fix docblock on DBAL::queryRecordset

# version 1.1.1 2016-06-16

- Fix bug in sdqlQuoteParseNumbers when `LC_NUMERIC` is `C`
- Add support for PHP CS Fixer and .php_cs file
- On tests ArrayLogger uses AbstractLogger

# version 1.1.0 2016-06-09

- Create CommonTypes interface with common types constants
- Move some methods to Traits
- Sqlite\DBAL::sqlString uses str_replace instead of \SQLite3::escapeString
- Sqlite\Settings now allow enable-exceptions
- Mysql\Settings now allow connect-timeout
- Mysql\DBAL::connect now uses set_charset instad of query SET NAMES
- Code style improvements
- Docblocks improvements

# version 1.0.0 2016-06-08

- Put this code as a library