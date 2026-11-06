<?php

include_once(__DIR__ . '/database/dbFoodOrder.php');

if (isset($_POST['order_id'])) {
    $order_id = htmlspecialchars($_POST['order_id'], ENT_QUOTES, 'UTF-8');
    $pdf = generateFoodOrderPDF($order_id);
}
?>



Hello World