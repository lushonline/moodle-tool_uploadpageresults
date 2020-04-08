# tool_uploadpageresults

[![Build Status](https://travis-ci.org/lushonline/moodle-tool_uploadpageresults.svg?branch=master)](https://travis-ci.org/lushonline/moodle-tool_uploadpageresults)

A tool to allow import of completion results for Page activities using a text delimited file.

The Course and Page activity should have been uploaded using the
[moodle-tool_uploadpage](https://github.com/lushonline/moodle-tool_uploadpage) as this correctly configures the Course and Activity ID Number.

The import file contains two columns:
|COLUMN ORDER|COLUMN HEADER|DESCRIPTION|REQUIRED|EXAMPLE|
|---------------|-------------|---------|----------|----------|
|1|COURSE_IDNUMBER|This is the Moodle Course ID Number. The ID number of a course is used when matching the results against the imported course and is not displayed anywhere to the user in Moodle. All course when imported using [moodle-tool_uploadpage](https://github.com/lushonline/moodle-tool_uploadpage) have an immutable COURSE_IDNUMBER|Yes|1b49aa30-e719-11e6-9835-f723b46a2688|
|2|USER_USERNAME|This is the Moodle Users username.|Yes|Student|

The import enrols the user into the Course, and marks the activity viewed, which for Page activity is the only completion requirment.

The date used for the viewed entry is the date of the import not the date of the completion as recorded in the external system.

- [Installation](#installation)
- [Usage](#usage)

## Installation

---

1. Install the uploadpage tool to support upload of courses.

   ```sh
   git clone https://github.com/lushonline/moodle-tool_uploadpage.git admin/tool/uploadpage
   ```

   Or install via the Moodle plugin directory:

   https://moodle.org/plugins/tool_uploadpage

3. Install this tool to support upload of results.

   ```sh
   git clone https://github.com/lushonline/moodle-tool_uploadpageresults.git admin/tool/uploadpageresults
   ```

   Or install via the Moodle plugin directory:

   https://moodle.org/plugins/tool_uploadpageresults

4. Then run the Moodle upgrade

This plugin requires no configuration.

## Usage

For more information see the [Wiki Pages](https://github.com/lushonline/moodle-tool_uploadpageresults/wiki)

## Acknowledgements

This was inspired in part by the great work of Frédéric Massart and Piers harding on the core [admin\tool\uploadcourse](https://github.com/moodle/moodle/tree/master/admin/tool/uploadcourse)
