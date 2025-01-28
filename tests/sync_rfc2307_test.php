<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for local_ldap.
 *
 * @package   local_ldap
 * @copyright 2024 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ldap;

// phpcs:disable moodle.PHPUnit.TestCaseNames.Missing

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/local/ldap/tests/sync_base_testcase.php');

/**
 * PHPUnit tests for local_ldap and OpenLDAP.
 *
 * @package   local_ldap
 * @copyright 2024 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_rfc2307_test extends sync_base_testcase {

    /**
     * Get the LDAP user type.
     *
     * @return string
     */
    protected function get_ldap_user_type(): string {
        return 'rfc2307';
    }

    /**
     * Get the object classes for an LDAP user.
     *
     * @return array
     */
    protected function get_ldap_user_object_classes(): array {
        return ['inetOrgPerson', 'organizationalPerson', 'person', 'posixAccount'];
    }

    /**
     * Get the attribute class used for synchronization.
     *
     * @return string
     */
    protected function get_ldap_user_attribute_class(): string {
        return 'employeeType';
    }

    /**
     * Return the approrpiate top-level OU depending on the environment.
     *
     * @return string The top-level OU.
     */
    protected function get_ldap_test_container(): string {
        return 'dc=moodletest';
    }

    /**
     * Return the approrpiate test OU depending on the environment.
     *
     * @return array The test container OU.
     */
    protected function get_ldap_test_ou(): array {
        $o = array();
        $o['objectClass'] = array('dcObject', 'organizationalUnit');
        $o['dc'] = 'moodletest';
        $o['ou'] = 'MOODLETEST';
        return $o;
    }
}
