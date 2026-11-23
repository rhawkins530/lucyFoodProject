
<?php
session_cache_expire(30);
session_start();

$loggedIn = isset($_SESSION['_id']);
$accessLevel = $_SESSION['access_level'] ?? 0;
$userID = $_SESSION['_id'] ?? null;

require_once('database/dbinfo.php');
require_once('database/dbInventory.php');
require_once('database/dbInventoryHistory.php');
require_once('database/dbUnsorted.php');
require_once('database/dbItemCategory.php');

$errors = [];

$CRATE_TYPES = ['Gaylord (M)', 'Blue Boy (L)', 'X-Large'];
$LOCATIONS = ['Main', 'Annex'];

/*
 * Load categories from the category database functions.
 * These populate both the Sorted dropdown and Unsorted checklist.
 */
$categories = get_all_ItemCategory();

if (isset($_POST['food-status'])) {
    $foodStatus = $_POST['food-status'];
    try {
        if ($foodStatus === 'Sorted') {
            $qty = filter_var(
                $_POST['box-quantity'] ?? '',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            $catId = filter_var(
                $_POST['category'] ?? '',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            $validCategoryIds = array_map(
                fn($category) => (int)$category->getId(),
                $categories
            );

            if (
                $qty === false ||
                $catId === false ||
                !in_array((int)$catId, $validCategoryIds, true)
            ) {
                throw new InvalidArgumentException(
                    'Please choose a category and a box quantity of 1 or more.'
                );
            }

            // Validate location for Sorted inventory only.
            $location = $_POST['location'] ?? '';

            if (!in_array($location, $LOCATIONS, true)) {
                throw new InvalidArgumentException(
                    'Please select a valid location.'
                );
            }

            // Find the selected category name.
            $categoryName = null;

            foreach ($categories as $category) {
                if ((int)$category->getId() === (int)$catId) {
                    $categoryName = $category->getName();
                    break;
                }
            }

            if ($categoryName === null) {
                throw new InvalidArgumentException(
                    'Invalid item category.'
                );
            }

            // Preserve existing inventory history functionality.
            $personId = $_SESSION['_personId'] ?? $userID;

            addHistory((int)$catId, (int)$qty, $personId);

            // Update or create inventory for the selected location.
            if (!changeInventoryQuantityByLocation(
                (int)$catId,
                $categoryName,
                (int)$qty,
                $location
            )) {
                throw new InvalidArgumentException(
                    'Could not save the inventory quantity.'
                );
            }

        } elseif ($foodStatus === 'Unsorted') {

            $weight = filter_var(
                $_POST['unsorted-weight'] ?? '',
                FILTER_VALIDATE_FLOAT
            );

            $crate = $_POST['crate-type'] ?? '';

            if ($weight === false || $weight <= 0) {
                throw new InvalidArgumentException(
                    'Weight must be greater than 0.'
                );
            }

            if (!in_array($crate, $CRATE_TYPES, true)) {
                throw new InvalidArgumentException(
                    'Please choose a valid crate type.'
                );
            }

            $validCategoryIds = array_map(
                fn($category) => (string)$category->getId(),
                $categories
            );

            $postedCategories = $_POST['categories'] ?? [];

            if (!is_array($postedCategories)) {
                $postedCategories = [];
            }

            $noneSelected = in_array(
                'None',
                $postedCategories,
                true
            );

            $selectedCategoryIds = array_values(array_unique(
                array_intersect(
                    array_map('strval', $postedCategories),
                    $validCategoryIds
                )
            ));

            if (!$noneSelected && empty($selectedCategoryIds)) {
                throw new InvalidArgumentException(
                    'Select "None" or at least one category.'
                );
            }

            // Unsorted inventory remains unchanged.
            addUnsortedInventory(
                (float)$weight,
                $crate,
                $noneSelected ? [] : $selectedCategoryIds
            );

        } else {
            throw new InvalidArgumentException(
                'Unknown food status.'
            );
        }

        header(
            'Location: ' .
            strtok($_SERVER['REQUEST_URI'], '?') .
            '?saved=1'
        );

        exit;

    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (mysqli_sql_exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>Add Inventory | CCDA</title>
    <style>

        pageheader {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .title {
            text-align: center;
            min-height: 3.5rem;
            width: auto;
            font-size: 2rem;
            font-weight: 600;
            color: var(--secondary-accent-color);
            padding: .4rem 1rem;
            border-radius: 10px;
            background-color: #ffffffee;
        }

        .report-section {
            max-width: 900px;
            margin: 2rem auto;
            background-color: #D4E7F6;
            border-radius: 15px;
            padding: 1.5rem;
        }

        .report-section h2 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--secondary-accent-color);
        }

        .input-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            margin-bottom: 1rem;
        }

        .input-group label {
            margin-bottom: .5rem;
        }

        #category-columns {
            column-count: 2;
            column-gap: 10%;
        }

        #category-columns label {
            display: block;
            break-inside: avoid;
            margin-bottom: .25rem;
        }

        #category-checklist {
            width: min(100%, 650px);
        }

        button {
            cursor: pointer;
        }

        #form-errors {
            color: #b00020;
            margin: 1rem;
        }

        @media (max-width: 480px) {

            #category-columns {
                column-count: 1;
            }

            .report-section {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <pageheader>
        <h1 class="title">Add Inventory</h1>
    </pageheader>
    <?php require_once('header.php'); ?>
    <main>
        <?php if (isset($_GET['saved'])): ?>
            <h4 id="saved-msg" style="color:black; margin-left:15%;">
                <i>Inventory submitted.</i>
            </h4>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <h4 style="color:red;">
                <i>
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </i>
            </h4>
        <?php endforeach; ?>

        <div class="report-section">
            <h2>Inventory Item Entry</h2>
            <form method="POST" id="inv-form" novalidate>
                <div id="form-errors" role="alert" style="display:none;"></div>

                <!-- FOOD STATUS -->
                <div class="input-group">
                    <label for="food-status">Food Status:</label>
                    <?php $selStatus = $_POST['food-status'] ?? 'Sorted'; ?>
                    <select id="food-status" name="food-status">
                        <option value="Sorted"
                            <?= $selStatus === 'Sorted' ? 'selected' : '' ?>>
                            Sorted
                        </option>
                        <option value="Unsorted"
                            <?= $selStatus === 'Unsorted' ? 'selected' : '' ?>>
                            Unsorted
                        </option>
                    </select>
                </div>

                <!-- SORTED FIELDS -->
                <div id="sorted-fields">
                    <div class="input-group">
                        <label for="category-options">Item Category:</label>
                        <select id="category-options" name="category">
                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        (string)$category->getId(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    <?= (string)($_POST['category'] ?? '') ===
                                        (string)$category->getId()
                                        ? 'selected'
                                        : '' ?>>
                                    <?= htmlspecialchars(
                                        $category->getName(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- LOCATION: SORTED ONLY -->
                    <div class="input-group">
                        <label for="location">Location:</label>
                        <select id="location" name="location" required>
                            <?php foreach ($LOCATIONS as $loc): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        $loc,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    <?= ($_POST['location'] ?? 'Main') === $loc
                                        ? 'selected'
                                        : '' ?>>
                                    <?= htmlspecialchars(
                                        $loc,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="box-quantity">Box Quantity:</label>
                        <input
                            type="number"
                            min="1"
                            step="1"
                            id="box-quantity"
                            name="box-quantity"
                            placeholder="Amount of boxes"
                            value="<?= htmlspecialchars(
                                $_POST['box-quantity'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>
                </div>

                <!-- UNSORTED FIELDS -->
                <div id="unsorted-fields" style="display:none;">
                    <div class="input-group">
                        <label for="unsorted-weight">Weight (lbs):</label>
                        <input
                            type="number"
                            id="unsorted-weight"
                            name="unsorted-weight"
                            min="0.01"
                            step="any"
                            placeholder="Enter weight in lbs."
                            value="<?= htmlspecialchars(
                                $_POST['unsorted-weight'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>

                    <div class="input-group">
                        <label>Categories Inside Unsorted Crate:</label>
                        <div id="category-checklist">
                            <div style="display:flex; gap:10%; margin-bottom:.5rem;">
                                <label>
                                    <input
                                        type="checkbox"
                                        id="cat-none"
                                        name="categories[]"
                                        value="None">
                                    None
                                </label>
                                <label>
                                    <input
                                        type="checkbox"
                                        id="cat-all">
                                    <strong>Select All</strong>
                                </label>
                            </div>
                            <div id="category-columns"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="crate-type">Crate Type:</label>
                        <select id="crate-type" name="crate-type">
                            <?php foreach ($CRATE_TYPES as $crate): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        $crate,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    <?= ($_POST['crate-type'] ?? '') === $crate
                                        ? 'selected'
                                        : '' ?>>
                                    <?= htmlspecialchars(
                                        $crate,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- SUBMIT -->
                <div class="input-group">
                    <button type="submit">
                        Submit to Inventory!
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script>

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('inv-form');
        const status = document.getElementById('food-status');
        const sortedBox = document.getElementById('sorted-fields');
        const unsortedBox = document.getElementById('unsorted-fields');
        const errorBox = document.getElementById('form-errors');
        const noneBox = document.getElementById('cat-none');
        const categorySel = document.getElementById('category-options');
        const allBox = document.getElementById('cat-all');
        const columns = document.getElementById('category-columns');
        const catBoxes = [];

        // Generate checklist categories from the database-backed dropdown.
        Array.from(categorySel.options).forEach(function (opt) {
            const label = document.createElement('label');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'categories[]';
            cb.value = opt.value;

            // Restore previously selected categories after an error.
            const previouslySelected = <?= json_encode(
                array_map(
                    'strval',
                    is_array($_POST['categories'] ?? null)
                        ? $_POST['categories']
                        : []
                )
            ) ?>;
            cb.checked = previouslySelected.includes(String(opt.value));
            label.appendChild(cb);
            label.appendChild(
                document.createTextNode(' ' + opt.text)
            );
            columns.appendChild(label);
            catBoxes.push(cb);
        });

        function syncAll() {
            allBox.checked = catBoxes.length > 0 &&
                catBoxes.every(function (cb) {
                    return cb.checked;
                });
        }

        allBox.addEventListener('change', function () {
            catBoxes.forEach(function (cb) {
                cb.checked = allBox.checked;
            });
            if (allBox.checked) {
                noneBox.checked = false;
            }
        });

        noneBox.addEventListener('change', function () {
            if (noneBox.checked) {
                catBoxes.forEach(function (cb) {
                    cb.checked = false;
                });
            }
            allBox.checked = false;
        });

        catBoxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) {
                    noneBox.checked = false;
                }
                syncAll();
            });
        });

        function setDisabled(container, disabled) {
            container.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = disabled;
            });
        }

        function updateFields() {
            const isUnsorted = status.value === 'Unsorted';
            sortedBox.style.display = isUnsorted ? 'none' : 'block';
            unsortedBox.style.display = isUnsorted ? 'block' : 'none';
            setDisabled(sortedBox, isUnsorted);
            setDisabled(unsortedBox, !isUnsorted);
            errorBox.style.display = 'none';
        }

        function validate() {
            const messages = [];
            if (status.value === 'Sorted') {
                const qty = Number(
                    document.getElementById('box-quantity').value
                );
                if (!categorySel.value) {
                    messages.push('Please choose an item category.');
                }
                if (!Number.isInteger(qty) || qty < 1) {
                    messages.push(
                        'Box quantity must be a whole number of 1 or more.'
                    );
                }
                if (!document.getElementById('location').value) {
                    messages.push('Please select a location.');
                }

            } else {
                const weight = document.getElementById('unsorted-weight').value;
                if (
                    weight === '' ||
                    isNaN(Number(weight)) ||
                    Number(weight) <= 0
                ) {
                    messages.push('Weight must be greater than 0.');
                }
                if (!document.getElementById('crate-type').value) {
                    messages.push('Please choose a crate type.');
                }
                if (
                    !noneBox.checked &&
                    !catBoxes.some(function (cb) {
                        return cb.checked;
                    })
                ) {
                    messages.push(
                        'Select "None" or at least one category.'
                    );
                }
            }
            return messages;
        }

        form.addEventListener('submit', function (event) {
            const messages = validate();
            if (messages.length) {
                event.preventDefault();
                errorBox.innerHTML = messages.map(function (message) {
                    return '<div>' + message + '</div>';
                }).join('');
                errorBox.style.display = 'block';
                errorBox.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

        status.addEventListener('change', function () {
            updateFields();
            const savedMsg = document.getElementById('saved-msg');
            if (savedMsg) {
                savedMsg.style.display = 'none';
            }
        });

        syncAll();
        updateFields();

        if (window.location.search.includes('saved=1')) {
            history.replaceState(
                null,
                '',
                window.location.pathname
            );
        }
    });
    </script>
</body>
</html>
