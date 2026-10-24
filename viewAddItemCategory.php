<?php
    session_cache_expire(30);
    session_start();

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    $errors = [];
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    if ($accessLevel < 2) {
        header('Location: index.php');
        die();
    }

    require_once('database/dbinfo.php');
    require_once('database/dbPersons.php');
    require_once('database/dbinventory.php');
    $con = connect();

    //New Categorys
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['name']);

            /* check that Items Per Box is set */
            if(isset($_POST["minimum"])){

                /* try to convert items per box to a number. If it cannot convert, leave it as a string */
                try{
                    $minimum = +$_POST["minimum"];
                }
                catch(TypeError  $e){
                    $minimum = " ";
                }

                /* check for errors */
                if(!is_int($minimum)){
                    $errors[] = 'Minimum boxes must be in whole numbers';
                }
                else if($minimum < 0){
                    $errors[] = 'Minimum boxes cannot be negative';
                }
            }
            else{
                $errors[] = 'Minimum boxes cannot be empty';
            }
            if(isset($_POST["allocated"])){

                /* try to convert items per box to a number. If it cannot convert, leave it as a string */
                try{
                    $allocated = +$_POST["allocated"];
                }
                catch(TypeError  $e){
                    $allocated = " ";
                }

                /* check for errors */
                if(!is_int($allocated)){
                    $errors[] = 'Allocated boxes must be in whole numbers';
                }
                else if($allocated < 0){
                    $errors[] = 'Allocated boxes cannot be negative';
                }
            }
            else{
                $errors[] = 'Minimum boxes cannot be empty';
            }

            if(empty($errors)){
                $existing_item = getItemByName($name);
                if ($existing_item) {
                    $errors[] = "Category already exists";

                } else {
                    addItem($name, $minimum, $allocated);

                    header("Location: viewItemCategories.php");
                    exit();
                }
            }
        }

?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc') ?>
    <title>Add Item Category | CCDA</title>
    <style>
        pageheader {
            margin-top: 3rem;
            display: flex; 
            justify-content: center; 
            align-items: center;
        }
        .title {
            text-align: center;
            height: 3.5rem;
            width:auto;
            font-size: 2rem;
            font-weight: 600;
            color: var(--secondary-accent-color);
            padding-top: .4rem;
            border-radius: 10px;
            background-color: #ffffffee;
            white-space: nowrap;
            overflow: hidden;
        }
        .report-container {
            max-width: 1100px;
            margin: 0 auto 4rem auto;
            padding: 1rem;
        }
        .report-section {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .report-section h2 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--secondary-accent-color);
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background-color: rgba(0,0,0,0.2);
            color: var(--page-font-color);
            text-decoration: none;
            border-radius: 0.25rem;
        }
        .back-btn:hover {
            background-color: rgba(0,0,0,0.3);
        }
        @media only screen and (max-width: 768px) {
            pageheader {
                top: 100px;
            }
            .title {
                border-radius: 0;
                background-color: #ffffff;
                width: 100%;
            }
            .report-container {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<pageheader>
    <h1 class="title">Add Item Category</h1>
</pageheader>
<body>
    <?php require_once('header.php'); ?>
    <main>
        <div class="report-container">
            <a href="viewItemCategories.php" class="back-btn">← Back</a>

            <?php if (!empty($errors)): ?>
                <ul>
                    <?php foreach($errors AS $error): ?>
                        <li><?php echo("<h4 style=\"color:red;\"><i>".$error."</i></h4>"); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class ="report-section">
                <h2>Add New Item Category</h2>
                <form method="POST" action= "viewAddItemCategory.php">
                    <div style="display:flex; flex-direction:column; gap:1rem; max-width:400px;">
                        <div>
                            <label>Category Name:</label><br>
                            <input type="text" name="name" required>
                        </div>
                        <div>
                            <label>Minimum Boxes:</label><br>
                            <input type="number" name="minimum" min="0" value="0" required>
                        </div>
                        <div>
                            <label>Allocated:</label><br>
                            <input type="number" name="allocated" min="0" value="0" required>
                        </div>
                        <div>
                            <input type="submit" name="add_category" value="Add Category" class="generate-btn">
                        </div>
                </form>
            </div>

        </div>
    </main>

</body>
</html>
