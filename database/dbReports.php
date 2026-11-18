<?php

include_once('dbinfo.php');

/*
    Make the name for a new report, without its id: the date and the report type, e.g.
    "11-18-2026-End-of-Year". addReport() adds the id to the front once the report is saved,
    which keeps names unique even when the same report is generated more than once a day.
    Parameters:
    - $reportType: The report type, e.g. 'End of Year Report'.
    - $dateCreated: When the report was generated, as 'Y-m-d H:i:s'.
    Returns:
    - The report name.
*/
function generateReportName($reportType, $dateCreated) {
    // "End of Year Report" -> "End-of-Year"
    $typeForName = str_replace(' ', '-', preg_replace('/ Report$/', '', $reportType));
    return date('m-d-Y', strtotime($dateCreated)) . '-' . $typeForName;
}

/*
    Save a generated report. The id is assigned by the database (AUTO_INCREMENT) and added to the
    front of the name, e.g. "5-11-18-2026-End-of-Year". file_path is left empty until reports
    are saved as PDFs.
    Parameters:
    - $reportType: The report type, e.g. 'End of Year Report'.
    - $creatorId: The dbpersons.id (username) of the person who generated the report.
    - $dateCreated: When the report was generated, as 'Y-m-d H:i:s'.
    Returns:
    - The new report's id if it was saved, false otherwise.
*/
function addReport($reportType, $creatorId, $dateCreated) {
    $name = generateReportName($reportType, $dateCreated);

    $con = connect();
    $escapedName = mysqli_real_escape_string($con, $name);
    $reportType = mysqli_real_escape_string($con, $reportType);
    $creatorId = mysqli_real_escape_string($con, $creatorId);
    $dateCreated = mysqli_real_escape_string($con, $dateCreated);

    try {
        // Save the report and add its id to the name together, so a report is never saved
        // without the id in its name
        $con->begin_transaction();

        $query = "INSERT INTO dbreports (name, report_type, date_created, creator_id)
                  VALUES ('$escapedName', '$reportType', '$dateCreated', '$creatorId')";
        if (!mysqli_query($con, $query)) {
            $con->rollback();
            mysqli_close($con);
            return false;
        }

        // The id is only known once the report is saved
        $reportId = mysqli_insert_id($con);
        $query = "UPDATE dbreports SET name = CONCAT(id, '-', name) WHERE id = $reportId";
        if (!mysqli_query($con, $query) || !$con->commit()) {
            $con->rollback();
            mysqli_close($con);
            return false;
        }
    }
    catch (Exception $e) {
        $con->rollback();
        mysqli_close($con);
        return false;
    }
    mysqli_close($con);
    return $reportId;
}

/*
    Get a report by its id
    Parameters:
    - $id: The dbreports id of the report.
    Returns:
    - The report as an associative array (id, name, report_type, date_created, creator_id,
      file_path), or null if there is no report with that id.
*/
function getReportById($id) {
    $con = connect();
    $id = (int)$id;
    $result = mysqli_query($con, "SELECT * FROM dbreports WHERE id = $id");
    $report = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $report ?: null;
}
