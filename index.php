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
 * Import a framework.
 *
 * @package    tool_uploadpageresults
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/phpunit/classes/util.php');

admin_externalpage_setup('tooluploadpageresults');

$pagetitle = get_string('pluginname', 'tool_uploadpageresults');

$context = context_system::instance();

$url = new moodle_url("/admin/tool/uploadpageresults/index.php");
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($pagetitle);

$importid      = optional_param('importid', '', PARAM_INT);
$confirm       = optional_param('confirm', '0', PARAM_BOOL);
$needsconfirm  = optional_param('needsconfirm', '0', PARAM_BOOL);


$text = null;
$encoding = null;
$delimiter = null;

// First time - import_form returns a 0, and import_confirm_form a 1.
if (empty($importid)) {
    $mform1 = new tool_uploadpageresults_import_form($url->out(false));
    // Was the first form submitted.
    if ($form1data = $mform1->get_data()) {
        $text = $mform1->get_file_content('importfile');
        $encoding = $form1data->encoding;
        $delimiter = $form1data->delimiter_name;
    } else {
        // First time.
        echo $OUTPUT->header();
        echo $OUTPUT->heading($pagetitle);
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
}

$importer = new tool_uploadpageresults_importer($text, $encoding, $delimiter);
unset($text);
$mform2 = new tool_uploadpageresults_import_confirm_form(null, $importer);

// Was the second form submitted.
if ($form2data = $mform2->is_cancelled()) {
    redirect($returnurl);
} else if ($form2data = $mform2->get_data()) {
    $importid = $form2data->importid;
    $category = $form2data->category;
    $importer = new tool_uploadpageresults_importer(null, null, null, $importid, $form2data);
    $error = $importer->get_error();
    if ($error) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadpageresultsresult', 'tool_uploadpageresults'));
        $records = $importer->execute(new tool_uploadpageresults_tracker(
                                            tool_uploadpageresults_tracker::OUTPUT_HTML)
                                     );
        echo $OUTPUT->continue_button($url);
    }
} else {
    // First time.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle);
    $mform2->display();
    echo $OUTPUT->footer();
    die();
}
echo $OUTPUT->footer();