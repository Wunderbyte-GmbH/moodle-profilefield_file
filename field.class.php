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
 * File profile field.
 *
 * @package    profilefield_file
 * @copyright  2014 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_file
 *
 * @copyright  2014 onwards Shamim Rezaie {@link http://foodle.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_file extends profile_field_base {
    /**
     * Add fields for editing a file profile field.
     * @param MoodleQuickForm $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        $mform->addElement(
            'filemanager',
            $this->inputname,
            format_string($this->field->name),
            null,
            $this->get_filemanageroptions()
        );
    }

    /**
     * Overwrite the base class to display the data for this field
     */
    public function display_data() {
        global $CFG;
        // Default formatting.
        $data = parent::display_data();

        $context = context_user::instance($this->userid, MUST_EXIST);
        $fs = get_file_storage();

        $dir = $fs->get_area_tree($context->id, 'profilefield_file', "files_{$this->fieldid}", 0);
        $files = $fs->get_area_files(
            $context->id,
            'profilefield_file',
            "files_{$this->fieldid}",
            0,
            'timemodified',
            false
        );

        $data = [];

        foreach ($files as $file) {
            $path = '/' . $context->id . '/profilefield_file/files_' . $this->fieldid . '/' .
                    $file->get_itemid() .
                    $file->get_filepath() .
                    $file->get_filename();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", $path, true);
            $filename = $file->get_filename();
            $data[] = html_writer::link($url, $filename);
        }

        $data = implode('<br />', $data);

        return $data;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $usernew data coming from the form
     */
    public function edit_save_data($usernew) {
        if (!isset($usernew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $usercontext = context_user::instance($this->userid, MUST_EXIST);
        file_save_draft_area_files(
            $usernew->{$this->inputname},
            $usercontext->id,
            'profilefield_file',
            "files_{$this->fieldid}",
            0,
            $this->get_filemanageroptions()
        );
        parent::edit_save_data($usernew);
    }

    /**
     * Just remove the field element if locked.
     * @param MoodleQuickForm $mform instance of the moodleform class
     * @todo improve this
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() && !has_capability('moodle/user:update', context_system::instance())) {
            $mform->removeElement($this->inputname);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param stdClass $data
     * @param stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        $info = file_get_draft_area_info($data);
        // We do need to have a value otherwise the file will not be shown on user's profile.
        return ($info['filecount'] > 0) ? '1' : '';
    }

    /**
     * Loads a user object with data for this field ready for the edit profile
     * form
     * @param stdClass $user a user object
     */
    public function edit_load_user_data($user) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        if ($this->userid && ($this->userid !== -1)) {
            $filemanagercontext = context_user::instance($this->userid);
        } else {
            $filemanagercontext = context_system::instance();
        }

        $draftitemid = file_get_submitted_draft_itemid($this->inputname);
        file_prepare_draft_area(
            $draftitemid,
            $filemanagercontext->id,
            'profilefield_file',
            "files_{$this->fieldid}",
            0,
            $this->get_filemanageroptions()
        );

        $user->{$this->inputname} = $draftitemid;
    }

    /**
     * Returns the options of the file manager
     * @return array an array of setting values
     */
    private function get_filemanageroptions() {
        return [
            'maxfiles' => $this->field->param1,
            'maxbytes' => $this->field->param2,
            'subdirs' => 0,
            'accepted_types' => '*',
        ];
    }
}
