<?php
    include_once('dbinfo.php');
    include_once(dirname(__FILE__).'/../domain/HistoryItem.php');

    function addHistory($inventory_id, $change_amount, $changer_id){
        $con = connect();
        $inventory_id      = (int)$inventory_id;
        $change_amount     = (int)$change_amount;
        $changer_id        = (int)$changer_id;

        $query = "INSERT INTO dbinventoryhistory
                (inventory_id, change_amount, change_date, changer_id)
            VALUES ($inventory_id, '$change_amount', NOW(), $changer_id)";
        $ok = mysqli_query($con, $query);
        mysqli_close($con);
        return $ok;
    }

    function getHistory($id){
        $con = connect();
        $id = (int)$id;

        $query = "SELECT * FROM dbinventoryhistory WHERE id = $id";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_assoc($result);
        $history = new HistoryItem($row['id'], $row['inventory_id'], $row['change_amount'], $row['change_date'], $row['changer_id']);
        mysqli_close($con);

        return $history;
    }

    function getAllHistoryItems(){
        $con = connect();
        $query = "SELECT * FROM dbinventoryhistory ORDER BY change_date DESC";
        $result = mysqli_query($con, $query);
        $history = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $historyItem = new HistoryItem($row['id'], $row['inventory_id'], $row['change_amount'], $row['change_date'], $row['changer_id']);
            $history[] = $historyItem;
        }
        mysqli_close($con);
        return $history;
    }

    function revertHistory($id){
        $con = connect();
        $id = (int)$id;
        $query = "DELETE FROM dbinventoryhistory WHERE id = $id";
        $ok = mysqli_query($con, $query);
        mysqli_close($con);

        return $ok;
    }
?>