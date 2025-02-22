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
 * @copyright 2016 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ldap;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/local/ldap/locallib.php');
require_once($CFG->dirroot.'/auth/ldap/tests/auth_ldap_test.php');
require_once($CFG->dirroot.'/auth/ldap/auth.php');
require_once($CFG->libdir.'/ldaplib.php');

// Detect server type; we assume rfc2307.
if (!defined('TEST_AUTH_LDAP_USER_TYPE')) {
    define('TEST_AUTH_LDAP_USER_TYPE', 'rfc2307');
}

/**
 * PHPUnit tests for local_ldap.
 *
 * @package   local_ldap
 * @copyright 2016 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class sync_base_testcase extends \advanced_testcase {

    public function test_cohort_group_sync() {
        global $CFG, $DB;

        $this->validate_environment();

        // Make sure we can connect the server.
        $connection = $this->connect_to_ldap();

        $this->enable_plugin();

        // Create new empty test container.
        $testcontainer = $this->get_ldap_test_container();
        $topdn = $testcontainer . ',' . TEST_AUTH_LDAP_DOMAIN;
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);

        $o = $this->get_ldap_test_ou();
        if (!ldap_add($connection, $topdn, $o)) {
            $this->markTestSkipped('Can not create test LDAP container.');
        }

        // Create 2000 users.
        $o = array();
        $o['objectClass'] = array('organizationalUnit');
        $o['ou']          = 'users';
        ldap_add($connection, 'ou='.$o['ou'].','.$topdn, $o);
        for ($i = 1; $i <= 2000; $i++) {
            $this->create_ldap_user($connection, $topdn, $i);
        }

        // Create department groups.
        $o = array();
        $o['objectClass'] = array('organizationalUnit');
        $o['ou']          = 'groups';
        ldap_add($connection, 'ou='.$o['ou'].','.$topdn, $o);
        $departments = array('english', 'history', 'english(bis)');
        foreach ($departments as $department) {
            $o = array();
            $o['objectClass'] = array('groupOfNames');
            $o['cn']          = $department;
            $o['member']      = array('cn=username1,ou=users,'.$topdn, 'cn=username2,ou=users,'.$topdn,
                    'cn=username5,ou=users,'.$topdn);
            ldap_add($connection, 'cn='.$o['cn'].',ou=groups,'.$topdn, $o);
        }

        // Create a bunch of empty groups to simulate a large deployment.
        for ($i = 1; $i <= 2000; $i++) {
            $u = rand(1, 2000);
            $o = array();
            $o['objectClass'] = array('groupOfNames');
            $o['cn']          = "emptygroup{$i}";
            $o['member']      = array("cn=username{$u},ou=users,".$topdn);
            ldap_add($connection, 'cn='.$o['cn'].',ou=groups,'.$topdn, $o);
        }

        // Create all employees group.
        $o = array();
        $o['objectClass'] = array('groupOfNames');
        $o['cn']          = 'allemployees';
        $o['member']      = array();
        for ($i = 1; $i <= 2000; $i++) {
            $o['member'][] = "cn=username{$i},ou=users,{$topdn}";
        }
        ldap_add($connection, 'cn='.$o['cn'].',ou=groups,'.$topdn, $o);

        // Configure the authentication plugin a bit.
        set_config('host_url', TEST_AUTH_LDAP_HOST_URL, 'auth_ldap');
        set_config('start_tls', 0, 'auth_ldap');
        set_config('ldap_version', 3, 'auth_ldap');
        set_config('ldapencoding', 'utf-8', 'auth_ldap');
        set_config('pagesize', '2', 'auth_ldap');
        set_config('bind_dn', TEST_AUTH_LDAP_BIND_DN, 'auth_ldap');
        set_config('bind_pw', TEST_AUTH_LDAP_BIND_PW, 'auth_ldap');
        set_config('user_type', TEST_AUTH_LDAP_USER_TYPE, 'auth_ldap');
        set_config('contexts', 'ou=users,'.$topdn.';ou=groups,'.$topdn, 'auth_ldap');
        set_config('search_sub', 0, 'auth_ldap');
        set_config('opt_deref', LDAP_DEREF_NEVER, 'auth_ldap');
        set_config('user_attribute', 'cn', 'auth_ldap');
        set_config('memberattribute', 'member', 'auth_ldap');
        set_config('memberattribute_isdn', 0, 'auth_ldap');
        set_config('creators', '', 'auth_ldap');
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_ldap');
        set_config('field_map_email', 'mail', 'auth_ldap');
        set_config('field_updatelocal_email', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_email', '0', 'auth_ldap');
        set_config('field_lock_email', 'unlocked', 'auth_ldap');
        set_config('field_map_firstname', 'givenName', 'auth_ldap');
        set_config('field_updatelocal_firstname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_firstname', '0', 'auth_ldap');
        set_config('field_lock_firstname', 'unlocked', 'auth_ldap');
        set_config('field_map_lastname', 'sn', 'auth_ldap');
        set_config('field_updatelocal_lastname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_lastname', '0', 'auth_ldap');
        set_config('field_lock_lastname', 'unlocked', 'auth_ldap');
        $this->assertEquals(2, $DB->count_records('user'));

        // Sync the users.
        $auth = get_auth_plugin('ldap');

        ob_start();
        $sink = $this->redirectEvents();
        $auth->sync_users(true);
        $events = $sink->get_events();
        $sink->close();
        ob_end_clean();

        // Check events, 2000 users created.
        $this->assertCount(2000, $events);

        // Add the cohorts.
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "History Department";
        $cohort->idnumber = 'history';
        $historyid = cohort_add_cohort($cohort);
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "English Department";
        $cohort->idnumber = 'english';
        $englishid = cohort_add_cohort($cohort);
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "English Department (bis)";
        $cohort->idnumber = 'english(bis)';
        $englishbisid = cohort_add_cohort($cohort);

        // We should find 2004 groups: the 2000 random groups, the three departments,
        // and the all employees group.
        $plugin = new \local_ldap();
        $groups = $plugin->ldap_get_grouplist();
        $this->assertEquals(2004, count($groups));

        // All three cohorts should have three members.
        $plugin->sync_cohorts_by_group();
        $members = $DB->count_records('cohort_members', array('cohortid' => $historyid));
        $this->assertEquals(3, $members);
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(3, $members);
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishbisid));
        $this->assertEquals(3, $members);

        // Remove a user and then ensure he's re-added.
        $members = $plugin->get_cohort_members($englishid);
        cohort_remove_member($englishid, current($members)->id);
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(2, $members);
        $plugin->sync_cohorts_by_group();
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(3, $members);

        // Add the big cohort.
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "All employees";
        $cohort->idnumber = 'allemployees';
        $allemployeesid = cohort_add_cohort($cohort);

        // The big cohort should have 2000 members.
        $plugin->sync_cohorts_by_group();
        $members = $DB->count_records('cohort_members', array('cohortid' => $allemployeesid));
        $this->assertEquals(2000, $members);

        // Add a user to a group in LDAP and ensure he'd added.
        ldap_mod_add($connection, "cn=history,ou=groups,$topdn",
            array($auth->config->memberattribute => "cn=username3,ou=users,$topdn"));
        $members = $DB->count_records('cohort_members', array('cohortid' => $historyid));
        $this->assertEquals(3, $members);
        $plugin->sync_cohorts_by_group();
        $members = $DB->count_records('cohort_members', array('cohortid' => $historyid));
        $this->assertEquals(4, $members);

        // Remove a user from a group in LDAP and ensure he's deleted.
        ldap_mod_del($connection, "cn=english,ou=groups,$topdn",
            array($auth->config->memberattribute => "cn=username2,ou=users,$topdn"));
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(3, $members);
        $plugin->sync_cohorts_by_group();
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(2, $members);

        // Cleanup.
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);
    }

    public function test_cohort_autocreation() {
        global $CFG, $DB;

        $this->validate_environment();

        // Make sure we can connect the server.
        $connection = $this->connect_to_ldap();

        $this->enable_plugin();

        // Create new empty test container.
        $testcontainer = $this->get_ldap_test_container();
        $topdn = $testcontainer . ',' . TEST_AUTH_LDAP_DOMAIN;
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);

        $o = $this->get_ldap_test_ou();
        if (!ldap_add($connection, $topdn, $o)) {
            $this->markTestSkipped('Can not create test LDAP container.');
        }

        // Create 5 users.
        $o = array();
        $o['objectClass'] = array('organizationalUnit');
        $o['ou']          = 'users';
        ldap_add($connection, 'ou='.$o['ou'].','.$topdn, $o);
        for ($i = 1; $i <= 5; $i++) {
            $this->create_ldap_user($connection, $topdn, $i);
        }

        // Create department groups.
        $o = array();
        $o['objectClass'] = array('organizationalUnit');
        $o['ou']          = 'groups';
        ldap_add($connection, 'ou='.$o['ou'].','.$topdn, $o);
        $departments = array('english', 'history', 'english(bis)');
        foreach ($departments as $department) {
            $o = array();
            $o['objectClass'] = array('groupOfNames');
            $o['cn']          = $department;
            $o['member']      = array('cn=username1,ou=users,'.$topdn, 'cn=username2,ou=users,'.$topdn,
                    'cn=username5,ou=users,'.$topdn);
            ldap_add($connection, 'cn='.$o['cn'].',ou=groups,'.$topdn, $o);
        }

        // Configure the authentication plugin a bit.
        set_config('host_url', TEST_AUTH_LDAP_HOST_URL, 'auth_ldap');
        set_config('start_tls', 0, 'auth_ldap');
        set_config('ldap_version', 3, 'auth_ldap');
        set_config('ldapencoding', 'utf-8', 'auth_ldap');
        set_config('pagesize', '2', 'auth_ldap');
        set_config('bind_dn', TEST_AUTH_LDAP_BIND_DN, 'auth_ldap');
        set_config('bind_pw', TEST_AUTH_LDAP_BIND_PW, 'auth_ldap');
        set_config('user_type', TEST_AUTH_LDAP_USER_TYPE, 'auth_ldap');
        set_config('contexts', 'ou=users,'.$topdn.';ou=groups,'.$topdn, 'auth_ldap');
        set_config('search_sub', 0, 'auth_ldap');
        set_config('opt_deref', LDAP_DEREF_NEVER, 'auth_ldap');
        set_config('user_attribute', 'cn', 'auth_ldap');
        set_config('memberattribute', 'member', 'auth_ldap');
        set_config('memberattribute_isdn', 0, 'auth_ldap');
        set_config('creators', '', 'auth_ldap');
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_ldap');
        set_config('field_map_email', 'mail', 'auth_ldap');
        set_config('field_updatelocal_email', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_email', '0', 'auth_ldap');
        set_config('field_lock_email', 'unlocked', 'auth_ldap');
        set_config('field_map_firstname', 'givenName', 'auth_ldap');
        set_config('field_updatelocal_firstname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_firstname', '0', 'auth_ldap');
        set_config('field_lock_firstname', 'unlocked', 'auth_ldap');
        set_config('field_map_lastname', 'sn', 'auth_ldap');
        set_config('field_updatelocal_lastname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_lastname', '0', 'auth_ldap');
        set_config('field_lock_lastname', 'unlocked', 'auth_ldap');
        $this->assertEquals(2, $DB->count_records('user'));

        // Configure the local plugin.
        set_config('cohort_synching_ldap_groups_autocreate_cohorts', true, 'local_ldap');

        // Sync the users.
        $auth = get_auth_plugin('ldap');

        ob_start();
        $sink = $this->redirectEvents();
        $auth->sync_users(true);
        $events = $sink->get_events();
        $sink->close();
        ob_end_clean();

        // Check events, 5 users created.
        $this->assertCount(5, $events);

        // Sync the cohorts.
        $plugin = new \local_ldap();
        $plugin->sync_cohorts_by_group();

        // All three cohorts should be created and have 3 members.
        $plugin->sync_cohorts_by_group();
        $historyid = $DB->get_field('cohort', 'id', array('name' => 'history'));
        $members = $DB->count_records('cohort_members', array('cohortid' => $historyid));
        $this->assertEquals(3, $members);
        $englishid = $DB->get_field('cohort', 'id', array('name' => 'english'));
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishid));
        $this->assertEquals(3, $members);
        $englishbisid = $DB->get_field('cohort', 'id', array('name' => 'english(bis)'));
        $members = $DB->count_records('cohort_members', array('cohortid' => $englishbisid));
        $this->assertEquals(3, $members);

        // Direct test of member function.
        $members = $plugin->ldap_get_group_members('history');
        $this->assertEquals(3, count($members));

        // Cleanup.
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);
    }

    public function test_cohort_attribute_sync() {
        global $CFG, $DB;

        $this->validate_environment();

        $connection = $this->connect_to_ldap();

        $this->enable_plugin();

        // Create new empty test container.
        $testcontainer = $this->get_ldap_test_container();
        $topdn = $testcontainer . ',' . TEST_AUTH_LDAP_DOMAIN;
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);

        $o = $this->get_ldap_test_ou();
        if (!ldap_add($connection, $topdn, $o)) {
            $this->markTestSkipped('Can not create test LDAP container.');
        }

        // Create 2000 users.
        $o = array();
        $o['objectClass'] = array('organizationalUnit');
        $o['ou']          = 'users';
        ldap_add($connection, 'ou='.$o['ou'].','.$topdn, $o);
        for ($i = 1; $i <= 2000; $i++) {
            $this->create_ldap_user($connection, $topdn, $i);
        }

        // All users will be employees. Odd users will be faculty. Even will be staff.
        // Some will be staff(pt).
        for ($i = 1; $i <= 2000; $i++) {
            ldap_mod_add($connection, "cn=username{$i},ou=users,$topdn",
                array($this->get_ldap_user_attribute_class() => 'employee'));
            if ($i % 2 == 1) {
                ldap_mod_add($connection, "cn=username{$i},ou=users,$topdn",
                    array($this->get_ldap_user_attribute_class() => 'faculty'));
            } else {
                ldap_mod_add($connection, "cn=username{$i},ou=users,$topdn",
                    array($this->get_ldap_user_attribute_class() => 'staff'));
            }
            if ($i % 50 == 0) {
                ldap_mod_add($connection, "cn=username{$i},ou=users,$topdn",
                    array($this->get_ldap_user_attribute_class() => 'staff(pt)'));
            }
        }

        // Configure the authentication plugin a bit.
        set_config('host_url', TEST_AUTH_LDAP_HOST_URL, 'auth_ldap');
        set_config('start_tls', 0, 'auth_ldap');
        set_config('ldap_version', 3, 'auth_ldap');
        set_config('ldapencoding', 'utf-8', 'auth_ldap');
        set_config('pagesize', '2', 'auth_ldap');
        set_config('bind_dn', TEST_AUTH_LDAP_BIND_DN, 'auth_ldap');
        set_config('bind_pw', TEST_AUTH_LDAP_BIND_PW, 'auth_ldap');
        set_config('user_type', TEST_AUTH_LDAP_USER_TYPE, 'auth_ldap');
        set_config('contexts', 'ou=users,'.$topdn, 'auth_ldap');
        set_config('search_sub', 0, 'auth_ldap');
        set_config('opt_deref', LDAP_DEREF_NEVER, 'auth_ldap');
        set_config('user_attribute', 'cn', 'auth_ldap');
        set_config('memberattribute', 'member', 'auth_ldap');
        set_config('memberattribute_isdn', 0, 'auth_ldap');
        set_config('creators', '', 'auth_ldap');
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_ldap');
        set_config('field_map_email', 'mail', 'auth_ldap');
        set_config('field_updatelocal_email', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_email', '0', 'auth_ldap');
        set_config('field_lock_email', 'unlocked', 'auth_ldap');
        set_config('field_map_firstname', 'givenName', 'auth_ldap');
        set_config('field_updatelocal_firstname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_firstname', '0', 'auth_ldap');
        set_config('field_lock_firstname', 'unlocked', 'auth_ldap');
        set_config('field_map_lastname', 'sn', 'auth_ldap');
        set_config('field_updatelocal_lastname', 'oncreate', 'auth_ldap');
        set_config('field_updateremote_lastname', '0', 'auth_ldap');
        set_config('field_lock_lastname', 'unlocked', 'auth_ldap');
        $this->assertEquals(2, $DB->count_records('user'));

        // Configure local plugin.
        set_config('cohort_synching_ldap_attribute_attribute', $this->get_ldap_user_attribute_class(), 'local_ldap');

        // Sync the users.
        $auth = get_auth_plugin('ldap');

        ob_start();
        $sink = $this->redirectEvents();
        $auth->sync_users(true);
        $events = $sink->get_events();
        $sink->close();
        ob_end_clean();

        // Check events, 2000 users created.
        $this->assertCount(2000, $events);

        // Add the cohorts.
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "All employees";
        $cohort->idnumber = 'employee';
        $employeeid = cohort_add_cohort($cohort);
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "All faculty";
        $cohort->idnumber = 'faculty';
        $facultyid = cohort_add_cohort($cohort);
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "All staff";
        $cohort->idnumber = 'staff';
        $staffid = cohort_add_cohort($cohort);
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = "All staff (pt)";
        $cohort->idnumber = 'staff(pt)';
        $staffptid = cohort_add_cohort($cohort);

        // Count the distinct attribute values.
        $plugin = new \local_ldap();
        $attributes = $plugin->get_attribute_distinct_values();
        $this->assertEquals(4, count($attributes));

        // Faculty and staff should have two members and staff(pt) should have one.
        $plugin->sync_cohorts_by_attribute();
        $members = $DB->count_records('cohort_members', array('cohortid' => $employeeid));
        $this->assertEquals(2000, $members);
        $members = $DB->count_records('cohort_members', array('cohortid' => $facultyid));
        $this->assertEquals(1000, $members);
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffid));
        $this->assertEquals(1000, $members);
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffptid));
        $this->assertEquals(40, $members);

        // Remove a user and then ensure he's re-added.
        $members = $plugin->get_cohort_members($staffid);
        cohort_remove_member($staffid, current($members)->id);
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffid));
        $this->assertEquals(999, $members);
        $plugin->sync_cohorts_by_attribute();
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffid));
        $this->assertEquals(1000, $members);

        // Add an affiliation in LDAP and ensure he'd added.
        ldap_mod_add($connection, "cn=username500,ou=users,$topdn",
            array($this->get_ldap_user_attribute_class() => 'faculty'));
        $members = $DB->count_records('cohort_members', array('cohortid' => $facultyid));
        $this->assertEquals(1000, $members);
        $plugin->sync_cohorts_by_attribute();
        $members = $DB->count_records('cohort_members', array('cohortid' => $facultyid));
        $this->assertEquals(1001, $members);

        // Remove a user from a group in LDAP and ensure he's deleted.
        ldap_mod_del($connection, "cn=username400,ou=users,$topdn",
            array($this->get_ldap_user_attribute_class() => 'staff'));
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffid));
        $this->assertEquals(1000, $members);
        $plugin->sync_cohorts_by_attribute();
        $members = $DB->count_records('cohort_members', array('cohortid' => $staffid));
        $this->assertEquals(999, $members);

        // Cleanup.
        $this->recursive_delete(TEST_AUTH_LDAP_DOMAIN, $testcontainer);
    }

    /**
     * Verify that we can run at least one test.
     */
    protected function validate_environment(): void {
        if (!extension_loaded('ldap')) {
            $this->markTestSkipped('LDAP extension is not loaded.');
        }

        $this->resetAfterTest();

        if (!defined('TEST_AUTH_LDAP_HOST_URL') || !defined('TEST_AUTH_LDAP_BIND_DN') || !defined('TEST_AUTH_LDAP_BIND_PW')
                || !defined('TEST_AUTH_LDAP_DOMAIN')) {
            $this->markTestSkipped('External LDAP test server not configured.');
        }

        if ($this->get_ldap_user_type() != TEST_AUTH_LDAP_USER_TYPE) {
            $this->markTestSkipped("Incompatible LDAP server type");
        }
    }

    /**
     * Return the approrpiate top-level OU depending on the environment.
     *
     * @return string The top-level OU.
     */
    abstract protected function get_ldap_test_container(): string;

    /**
     * Return the approrpiate test OU depending on the environment.
     *
     * @return array The test container OU.
     */
    abstract protected function get_ldap_test_ou(): array;

    /**
     * Create an LDAP user in the test environment.
     *
     * Copied from auth_ldap_plugin_testcase\create_ldap_user. Extending that test
     * environment caused all manner of problems; forking was more straightforward.
     *
     * @param resource $connection the LDAP connection
     * @param string $topdn the top-level container
     * @param integer $i incremented number for user uniqueness constraint
     */
    protected function create_ldap_user($connection, $topdn, $i) {
        $o = array();
        $o['objectClass']   = $this->get_ldap_user_object_classes();
        $o['cn']            = 'username'.$i;
        $o['sn']            = 'Lastname'.$i;
        $o['givenName']     = 'Firstname'.$i;
        $o['uid']           = $o['cn'];
        $o['uidnumber']     = 2000 + $i;
        $o['gidNumber']     = 1000 + $i;
        $o['homeDirectory'] = '/';
        $o['mail']          = 'user'.$i.'@example.com';
        $o['userPassword']  = 'pass'.$i;
        ldap_add($connection, 'cn='.$o['cn'].',ou=users,'.$topdn, $o);
    }

    /**
     * Get the object classes for an LDAP user.
     *
     * @return array
     */
    abstract protected function get_ldap_user_object_classes(): array;

    /**
     * Get the attribute class used for synchronization.
     *
     * @return string
     */
    abstract protected function get_ldap_user_attribute_class(): string;

    /**
     * Get the LDAP user type.
     *
     * @return string
     */
    abstract protected function get_ldap_user_type(): string;

    /**
     * Delete an LDAP user in the test environment.
     *
     * Copied from auth_ldap_plugin_testcase\delete_ldap_user. Extending that test
     * environment caused all manner of problems; forking was more straightforward.
     *
     * @param resource $connection the LDAP connection
     * @param string $topdn the top-level container
     * @param integer $i incremented number for user uniqueness constraint
     */
    protected function delete_ldap_user($connection, $topdn, $i) {
        ldap_delete($connection, 'cn=username'.$i.',ou=users,'.$topdn);
    }

    /**
     * Activate the LDAP authentication plugin.
     *
     * Copied from auth_ldap_plugin_testcase\enable_plugin. Extending that test
     * environment caused all manner of problems; forking was more straightforward.
     */
    protected function enable_plugin() {
        $auths = get_enabled_auth_plugins(true);
        if (!in_array('ldap', $auths)) {
            $auths[] = 'ldap';

        }
        set_config('auth', implode(',', $auths));
    }

    /**
     * Connect to the LDAP server.
     *
     * @return resource
     */
    protected function connect_to_ldap() {
        $debuginfo = '';
        if (!$connection = ldap_connect_moodle(TEST_AUTH_LDAP_HOST_URL, 3, TEST_AUTH_LDAP_USER_TYPE, TEST_AUTH_LDAP_BIND_DN,
                TEST_AUTH_LDAP_BIND_PW, LDAP_DEREF_NEVER, $debuginfo, false)) {
            $this->markTestSkipped('Can not connect to LDAP test server: '.$debuginfo);
            return false;
        }
        return $connection;
    }

    /**
     * Clear out the test environment. We create a separate connection in case
     * pagination is required.
     *
     * @param string $dn The top level distinguished name
     * @param string $filter LDAP filter.
     */
    protected function recursive_delete($dn, $filter) {
        $ldapconnection = $this->connect_to_ldap();

        if ($res = ldap_list($ldapconnection, $dn, $filter, array('dn'))) {
            $info = ldap_get_entries($ldapconnection, $res);

            if ($info['count'] > 0) {
                $ldappagedresults = ldap_paged_results_supported(3, $ldapconnection);
                $ldapcookie = '';
                $todelete = array();
                $servercontrols = array();
                do {
                    if ($ldappagedresults) {
                        $servercontrols = array(
                            array(
                                'oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => array(
                                    'size' => 250, 'cookie' => $ldapcookie
                                )
                            )
                        );
                    }
                    $res = ldap_search($ldapconnection, "$filter,$dn", 'cn=*', array('dn'),
                        0, -1, -1, LDAP_DEREF_NEVER, $servercontrols);
                    if (!$res) {
                        continue;
                    }
                    $info = ldap_get_entries($ldapconnection, $res);
                    foreach ($info as $i) {
                        if (isset($i['dn'])) {
                            $todelete[] = $i['dn'];
                        }
                    }
                    if ($ldappagedresults) {
                        $ldapcookie = '';
                        ldap_parse_result($ldapconnection, $res, $errcode, $matcheddn,
                            $errmsg, $referrals, $controls);
                        if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                            $ldapcookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                        }
                    }
                    ldap_free_result($res);
                } while ($ldappagedresults && $ldapcookie !== null && $ldapcookie != '');

                if ($ldappagedresults) {
                    ldap_close($ldapconnection);
                    unset($ldapconnection);
                    $ldapconnection = $this->connect_to_ldap();
                }
                if (is_array($todelete)) {
                    foreach ($todelete as $delete) {
                        ldap_delete($ldapconnection, $delete);
                    }
                }
                $todelete = array();

                do {
                    if ($ldappagedresults) {
                        $servercontrols = array(
                            array(
                                'oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => array(
                                    'size' => 250, 'cookie' => $ldapcookie
                                )
                            )
                        );
                    }
                    $res = ldap_search($ldapconnection, "$filter,$dn", 'ou=*', array('dn'),
                        0, -1, -1, LDAP_DEREF_NEVER, $servercontrols);
                    if (!$res) {
                        continue;
                    }
                    $info = ldap_get_entries($ldapconnection, $res);
                    foreach ($info as $i) {
                        if (isset($i['dn']) && $info[0]['dn'] != $i['dn']) {
                            $todelete[] = $i['dn'];
                        }
                    }
                    if ($ldappagedresults) {
                        $ldapcookie = '';
                        ldap_parse_result($ldapconnection, $res, $errcode, $matcheddn,
                            $errmsg, $referrals, $controls);
                        if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                            $ldapcookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                        }
                    }
                    ldap_free_result($res);
                } while ($ldappagedresults && $ldapcookie !== null && $ldapcookie != '');

                if ($ldappagedresults) {
                    ldap_close($ldapconnection);
                    unset($ldapconnection);
                    $ldapconnection = $this->connect_to_ldap();
                }

                if (is_array($todelete)) {
                    foreach ($todelete as $delete) {
                        ldap_delete($ldapconnection, $delete);
                    }
                }

                ldap_delete($ldapconnection, "$filter,$dn");
            }
        }
        ldap_close($ldapconnection);
        unset($ldapconnection);
    }
}
