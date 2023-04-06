# Changelog

## v3.11.1 (April 6, 2023)

- Fixed AD pagination issue introduced in v3.11.0
- Fixed issue with empty groups in AD (props [@djlauk](https://github.com/djlauk))

## v3.11.0 (February 27, 2023)

- Dropped support Moodle 3.7-3.10
- Fixed countable issues under PHP 8.0
- Rebuilt pagination to remove pre-PHP 7.3 support

## 3.7.1 (May 17, 2021)

- Prevent mass-unenrollment when a connection fails

## 3.7.0 (November 9, 2020)

- Update pagination for PHP 7.4
- Change default branch to "main"
- Update CI tool to version 3
- Dropped support for Moodle 3.6

## 3.6.0 (June 15, 2020)

- Code cleanup
- Streamlined unit testing matrix
- Dropped support for Moodle 3.4 and 3.5

## 3.4.2 (May 19, 2019)

- Minor code cleanup and internal documentation fixes

## 3.4.1 (September 7, 2018)

- Fixed bug where attribute syncing could fail in large Active Directory environments
- Fixed bug where group syncing could fail in large Active Directory environments
- Updated tests to use large data sets
- Added optional unit test support for Active Directory

## 3.4.0 (May 4, 2018)

- Updated for GDPR compliance
- Fixed bug where parentheses were not filtered correctly (thanks to [@cperves](https://github.com/cperves) for the report)

## 3.3.0 (August 9, 2017)

- Changed version numbering to match stable version
- Bugfix for [MDL-57558](https://tracker.moodle.org/browse/MDL-57558): attribute sync was broken by Moodle 3.3.1

## 2.0.1 (April 24, 2017)

- Updated tests to support [MDL-12689](https://tracker.moodle.org/browse/MDL-12689)

## 2.0.0 (July 15, 2015)

- Official support for Moodle 2.9-Moodle 3.1
- Migrated CLI script to scheduled task
- Unit test coverage for OpenLDAP
