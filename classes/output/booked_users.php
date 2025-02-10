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
 * This file contains the definition for the renderable classes for booked users.
 *
 * It is used to display a configurable list of booked users for a given context.
 *
 * @package     mod_booking
 * @copyright   2024 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\output;

use mod_booking\booking_answers;
use mod_booking\table\manageusers_table;
use renderer_base;
use renderable;
use templatable;

/**
 * This file contains the definition for the renderable classes for booked users.
 *
 * It is used to display a configurable list of booked users for a given context.
 *
 * @package     mod_booking
 * @copyright   2024 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booked_users implements renderable, templatable {
    /** @var string $bookedusers rendered table of bookedusers */
    public $bookedusers;

    /** @var string $waitinglist rendered table of waitinglist */
    public $waitinglist;

    /** @var string $reservedusers rendered table of reservedusers */
    public $reservedusers;

    /** @var string $userstonotify rendered table of userstonotify */
    public $userstonotify;

    /** @var string $deletedusers rendered table of deletedusers */
    public $deletedusers;

    /**
     * Constructor
     *
     * @param string $scope can be system, course, instance or option
     * @param int $scopeid id matching the scope, e.g. optionid for scope 'option'
     * @param bool $showbooked
     * @param bool $showwaiting
     * @param bool $showreserved
     * @param bool $showtonotify
     * @param bool $showdeleted
     *
     */
    public function __construct(
        string $scope = 'system',
        int $scopeid = 0,
        bool $showbooked = false,
        bool $showwaiting = false,
        bool $showreserved = false,
        bool $showtonotify = false,
        bool $showdeleted = false
    ) {
        if ($scope == 'optiondate') {
            // For optiondates we only show booked users.
            // Also, we have no delete action but presence tracking.
            $bookeduserscols = ['lastname', 'firstname', 'email', 'status', 'notes', 'actions'];
            $bookedusersheaders = [
                get_string('lastname', 'core'),
                get_string('firstname', 'core'),
                get_string('email', 'core'),
                get_string('presence', 'mod_booking'),
                get_string('notes', 'mod_booking'),
                get_string('actions', 'mod_booking'),
            ];
        } else {
            // Define columns and headers for the tables.
            $bookeduserscols = ['name', 'action_delete'];
            $waitinglistcols = ['name', 'action_confirm_delete'];
            $reserveduserscols = ['name', 'action_delete'];
            $userstonotifycols = ['name', 'action_delete'];
            $deleteduserscols = ['name', 'timemodified'];

            $bookedusersheaders = [
                get_string('user', 'core'),
                get_string('delete', 'mod_booking'),
            ];
            $waitinglistheaders = [
                get_string('user', 'core'),
                get_string('delete', 'mod_booking'),
            ];
            $reservedusersheaders = [
                get_string('user', 'core'),
                get_string('delete', 'mod_booking'),
            ];
            $userstonotifyheaders = [
                get_string('user', 'core'),
                get_string('delete', 'mod_booking'),
            ];
            $deletedusersheaders = [
                get_string('user', 'core'),
                get_string('date'),
            ];
        }

        // If the scope contains more than one option...
        // ...then we have to add an option column!
        if (!in_array($scope, ['option', 'optiondate'])) {
            array_unshift($bookeduserscols, 'option');
            array_unshift($waitinglistcols, 'option');
            array_unshift($reserveduserscols, 'option');
            array_unshift($userstonotifycols, 'option');
            array_unshift($deleteduserscols, 'option');

            array_unshift($bookedusersheaders, get_string('bookingoption', 'mod_booking'));
            array_unshift($waitinglistheaders, get_string('bookingoption', 'mod_booking'));
            array_unshift($reservedusersheaders, get_string('bookingoption', 'mod_booking'));
            array_unshift($userstonotifyheaders, get_string('bookingoption', 'mod_booking'));
            array_unshift($deletedusersheaders, get_string('bookingoption', 'mod_booking'));
        } else if ($scope == 'option') {
            // We are in 'option' scope.
            if (get_config('booking', 'waitinglistshowplaceonwaitinglist')) {
                array_unshift($waitinglistcols, 'rank');
                array_unshift($waitinglistheaders, get_string('rank', 'mod_booking'));
            }
        }

        $this->bookedusers = $showbooked ?
            $this->render_users_table(
                $scope,
                $scopeid,
                MOD_BOOKING_STATUSPARAM_BOOKED,
                'booked',
                $bookeduserscols,
                $bookedusersheaders
            ) : null;

        // For optiondate scope, we only show booked users.
        if ($scope != 'optiondate') {
            $this->waitinglist = $showwaiting ? $this->render_users_table(
                $scope,
                $scopeid,
                MOD_BOOKING_STATUSPARAM_WAITINGLIST,
                'waitinglist',
                $waitinglistcols,
                $waitinglistheaders,
                true
            ) : null;

            $this->reservedusers = $showreserved ? $this->render_users_table(
                $scope,
                $scopeid,
                MOD_BOOKING_STATUSPARAM_RESERVED,
                'reserved',
                $reserveduserscols,
                $reservedusersheaders,
            ) : null;

            $this->userstonotify = $showtonotify ? $this->render_users_table(
                $scope,
                $scopeid,
                MOD_BOOKING_STATUSPARAM_NOTIFYMELIST,
                'notifymelist',
                $userstonotifycols,
                $userstonotifyheaders
            ) : null;

            $this->deletedusers = $showdeleted ? $this->render_users_table(
                $scope,
                $scopeid,
                MOD_BOOKING_STATUSPARAM_DELETED,
                'deleted',
                $deleteduserscols,
                $deletedusersheaders,
                false,
                true
            ) : null;
        }
    }

    /**
     * Render users table based on status param
     *
     * @param string $scope
     * @param int $scopeid
     * @param int $statusparam
     * @param string $tablenameprefix
     * @param array $columns
     * @param array $headers
     * @param bool $sortable
     * @param bool $paginate
     * @return ?string
     */
    private function render_users_table(
        string $scope,
        int $scopeid,
        int $statusparam,
        string $tablenameprefix,
        array $columns,
        array $headers = [],
        bool $sortable = false,
        bool $paginate = false
    ): ?string {
        [$fields, $from, $where, $params] = booking_answers::return_sql_for_booked_users($scope, $scopeid, $statusparam);

        $tablename = "{$tablenameprefix}_{$scope}_{$scopeid}";
        $table = new manageusers_table($tablename);

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // Todo: $table->define_baseurl() ...
        $table->define_cache('mod_booking', "bookedusertable");
        $table->define_columns($columns);
        $table->define_headers($headers);

        if ($sortable) {
            $table->sortablerows = true;
        }

        if ($paginate) {
            $table->use_pages = true;
        }

        $table->set_sql($fields, $from, $where, $params);

        // Checkboxes are currently only supported in option scope.
        if ($scope === 'option') {
            $table->addcheckboxes = true;

            // Show modal, single call, use selected items.
            $table->actionbuttons[] = [
                'iclass' => 'fa fa-trash mr-1', // Add an icon before the label.
                'label' => get_string('delete', 'moodle'),
                'class' => 'btn btn-sm btn-danger ml-2 mb-2',
                'href' => '#',
                'methodname' => 'delete_checked_booking_answers',
                // To include a dynamic form to open and edit entry in modal.
                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /* 'formname' => 'local_myplugin\\form\\edit_mytableentry', */
                'nomodal' => false,
                'selectionmandatory' => true,
                'id' => -1,
                'data' => [
                    'id' => 'id',
                    'titlestring' => 'delete',
                    'bodystring' => 'deletecheckedanswersbody',
                    // Localized title to be displayed as title in dynamic form (formname).
                    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    'submitbuttonstring' => 'delete',
                    'component' => 'mod_booking',
                    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    /* 'labelcolumn' => 'name', */
                ],
            ];
        }

        $html = $table->outhtml(20, false);
        return count($table->rawdata) > 0 ? $html : null;
    }

    /**
     * Export for template.
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return array_filter([
            'bookedusers' => $this->bookedusers ?? null,
            'waitinglist' => $this->waitinglist ?? null,
            'reservedusers' => $this->reservedusers ?? null,
            'userstonotify' => $this->userstonotify ?? null,
            'deletedusers' => $this->deletedusers ?? null,
        ]);
    }
}
