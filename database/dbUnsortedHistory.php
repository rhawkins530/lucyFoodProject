<?php
    include_once('dbinfo.php');
    include_once(dirname(__FILE__).'/../domain/UnsortedHistoryItem.php');

    function addUnsortedHistory($unsorted_id, $change_amount, $changer_id){
        $con = connect();
        $unsorted_id      = (int)$unsorted_id;
        $change_amount     = (int)$change_amount;
        $changer_id        = (int)$changer_id;

        $query = "INSERT INTO dbunsortedhistory
                (unsorted_id, change_amount, change_date, changer_id)
            VALUES ($unsorted_id, '$change_amount', NOW(), $changer_id)";
        $ok = mysqli_query($con, $query);
        mysqli_close($con);
        return $ok;
    }

    function getUnsortedHistory($id){
        $con = connect();
        $id = (int)$id;

        $query = "SELECT * FROM dbunsortedhistory WHERE id = $id";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_assoc($result);
        $history = new UnsortedHistoryItem($row['id'], $row['unsorted_id'], $row['change_amount'], $row['change_date'], $row['changer_id']);
        mysqli_close($con);

        return $history;
    }

    function getAllUnsortedHistoryItems(){
        $con = connect();
        $query = "SELECT * FROM dbunsortedhistory ORDER BY change_date DESC";
        $result = mysqli_query($con, $query);
        $history = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $historyItem = new UnsortedHistoryItem($row['id'], $row['unsorted_id'], $row['change_amount'], $row['change_date'], $row['changer_id']);
            $history[] = $historyItem;
        }
        mysqli_close($con);
        return $history;
    }

    function revertUnsortedHistory($id){
        $con = connect();
        $id = (int)$id;
        $query = "DELETE FROM dbunsortedhistory WHERE id = $id";
        $ok = mysqli_query($con, $query);
        mysqli_close($con);

        return $ok;
    }
?>