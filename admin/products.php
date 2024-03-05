<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:admin_login.php');
    exit(); // Add exit to stop further execution
}

$message = []; // Initialize an empty array to store messages

if (isset($_POST['add_product'])) {

    // Retrieve form data and sanitize
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_STRING);
    $details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);

    // File 1
    $image_01 = $_FILES['image_01']['name'];
    $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
    $image_size_01 = $_FILES['image_01']['size'];
    $image_folder_01 = '../uploaded_img/' . $image_01;

    // File 2
    $image_02 = $_FILES['image_02']['name'];
    $image_tmp_name_02 = $_FILES['image_02']['tmp_name'];
    $image_size_02 = $_FILES['image_02']['size'];
    $image_folder_02 = '../uploaded_img/' . $image_02;

    // File 3
    $image_03 = $_FILES['image_03']['name'];
    $image_tmp_name_03 = $_FILES['image_03']['tmp_name'];
    $image_size_03 = $_FILES['image_03']['size'];
    $image_folder_03 = '../uploaded_img/' . $image_03;

    // Check if product name already exists
    $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
    $select_products->execute([$name]);

    if ($select_products->rowCount() > 0) {
        $message[] = 'Product name already exists!';
    } else {

        // Check if all images are within size limit
        if ($image_size_01 > 2000000 || $image_size_02 > 2000000 || $image_size_03 > 2000000) {
            $message[] = 'Image size is too large!';
        } else {
            // Move images to upload folder
            if (move_uploaded_file($image_tmp_name_01, $image_folder_01) &&
                move_uploaded_file($image_tmp_name_02, $image_folder_02) &&
                move_uploaded_file($image_tmp_name_03, $image_folder_03)) {

                // Insert product into database
                $insert_products = $conn->prepare("INSERT INTO `products`(name, details, price, image_01, image_02, image_03) VALUES(?,?,?,?,?,?)");
                if ($insert_products->execute([$name, $details, $price, $image_01, $image_02, $image_03])) {
                    $message[] = 'New product added!';
                } else {
                    $message[] = 'Failed to add product to database!';
                }
            } else {
                $message[] = 'Failed to move uploaded images to destination folder!';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_product_image = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
    $delete_product_image->execute([$delete_id]);
    $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
    unlink('../uploaded_img/' . $fetch_delete_image['image_01']);
    unlink('../uploaded_img/' . $fetch_delete_image['image_02']);
    unlink('../uploaded_img/' . $fetch_delete_image['image_03']);
    $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
    $delete_product->execute([$delete_id]);
    $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
    $delete_cart->execute([$delete_id]);
    $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
    $delete_wishlist->execute([$delete_id]);
    header('location:products.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
</head>

<body>

    <?php include '../components/admin_header.php'; ?>

    <section class="add-products">
        <h1 class="heading">Add Product</h1>
        <?php
        // Display messages if any
        foreach ($message as $msg) {
            echo "<p>$msg</p>";
        }
        ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="flex">
                <div class="inputBox">
                    <span>Product Name (required)</span>
                    <input type="text" class="box" required maxlength="100" placeholder="Enter product name" name="name">
                </div>

                <div class="inputBox">
                    <span>Product Price (required)</span>
                    <input type="number" min="0" class="box" required max="9999999999" placeholder="Enter product price" onkeypress="if(this.value.length == 10) return false;" name="price">
                </div>
                <div class="inputBox">
                    <span>Image 01 (required)</span>
                    <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Image 02 (required)</span>
                    <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Image 03 (required)</span>
                    <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
                </div>
                <div class="inputBox">
                    <span>Product Category (required)</span>
                    <select name="details" class="box" required>
    <option value="" selected disabled>Select product details</option>
    <option value="A1 Bundle">A1 Bundle</option>
    <option value="A2 Bundle">A2 Bundle</option>
    <option value="A3 Paper">A3 Paper</option>
    <option value="A4 Paper">A4 Paper</option>
    <option value="A5 Bundle">A5 Bundle</option>
    <option value="B5 Bundle">B5 Bundle</option>
    <option value="B4 Bundle">B4 Bundle</option>
 \>
     
    <!-- Add more options as needed -->
</select>
                </div>
            </div>

            <input type="submit" value="Add Product" class="btn" name="add_product">
        </form>
    </section>

    <section class="show-products">
        <h1 class="heading">Products Added.</h1>
        <div class="box-container">
            <?php
            $select_products = $conn->prepare("SELECT * FROM `products`");
            $select_products->execute();
            if ($select_products->rowCount() > 0) {
                while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
            ?>
                    <div class="box">
                        <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="">
                        <div class="name"><?= $fetch_products['name']; ?></div>
                        <div class="price">LKR.<span><?= $fetch_products['price']; ?></span>/-</div>
                        <div class="details"><span><?= $fetch_products['details']; ?></span></div>
                        <div class="flex-btn">
                            <a href="update_product.php?update=<?= $fetch_products['id']; ?>" class="option-btn">Update</a>
                            <a href="products.php?delete=<?= $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<p class="empty">No products added yet!</p>';
            }
            ?>
        </div>
    </section>

    <script src="../js/admin_script.js"></script>
</body>

</html>
