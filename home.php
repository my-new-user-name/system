
<?php
session_start();
include('db.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$selectedUser = '';

if (isset($_GET['user'])) {
    $selectedUser = mysqli_real_escape_string($conn, $_GET['user']);
    $showChatBox = true;
} else {
    $showChatBox = false;
}

// Handle search functionality
$searchTerm = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
}

// Handle sold button action
if (isset($_POST['mark_sold']) && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $productUsername = mysqli_real_escape_string($conn, $_POST['product_username']);
    
    // Check if the current user is the owner of the product
    if ($productUsername === $username) {
        $updateSql = "UPDATE products SET status = 'sold' WHERE id = ? AND username = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("is", $productId, $username);
        
        if ($updateStmt->execute()) {
            header("Location: home.php");
            exit();
        }
    }
}

// Handle archive button action
if (isset($_POST['archive_product']) && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $productUsername = mysqli_real_escape_string($conn, $_POST['product_username']);
    
    // Check if the current user is the owner of the product
    if ($productUsername === $username) {
        $updateSql = "UPDATE products SET status = 'archived' WHERE id = ? AND username = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("is", $productId, $username);
        
        if ($updateStmt->execute()) {
            header("Location: home.php");
            exit();
        }
    }
}

// Handle add product form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item'])) {
    // Get form data
    $item = mysqli_real_escape_string($conn, $_POST['item']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    
    // Check if the uploads directory exists, if not create it
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Handle photo upload
    $photoPath = "";
    if (isset($_FILES['photos']) && $_FILES['photos']['error'][0] == 0) {
        $fileName = $_FILES['photos']['name'][0];
        $fileType = $_FILES['photos']['type'][0];
        $fileSize = $_FILES['photos']['size'][0];
        $fileTmpName = $_FILES['photos']['tmp_name'][0];
        
        // Generate a unique filename to avoid overwriting
        $uniqueName = time() . '_' . $username . '_' . $fileName;
        $uploadPath = $uploadDir . $uniqueName;
        
        // Check if file is an image
        $validExtensions = array('jpg', 'jpeg', 'png', 'gif');
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileExtension, $validExtensions)) {
            // Move the uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $photoPath = $uploadPath;
            } else {
                $error_message = "Failed to upload image.";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        // No image uploaded or error occurred
        $photoPath = "placeholder.jpg";
    }
    
    // Insert product into database with default status 'available'
    if (!isset($error_message)) {
        $sql = "INSERT INTO products (username, item, price, address, details, photo, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsss", $username, $item, $price, $address, $details, $photoPath);
        
        if ($stmt->execute()) {
            header("Location: home.php");
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Chat</title>
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Happy Thrift</h1>
       
        <div class="header-right">
            <a href="userprofile.php" class="profile"><i class='bx bx-user-circle'></i></a>
            <a href="message.php" class="see-all-messages-btn"><i class='bx bxl-messenger'></i></a>
            
            <!-- Messages dropdown -->
            <div class="messages-dropdown">
                <button class="messages-btn"><i class='bx bx-envelope' color='#000'></i></button>
                <ul class="messages-list">
                    <?php 
                    $sql = "SELECT username FROM users WHERE username != ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        $user = ucfirst($row['username']);
                        echo "<li><a href='#' class='select-user' data-user='" . htmlspecialchars($row['username']) . "'>$user</a></li>";
                    }
                    ?>
                </ul>
            </div>

            <a href="edit_profile.php" class="settings"><i class='bx bx-cog'></i></a>
            <a href="archive.php" class="archive-link"><i class='bx bx-archive'></i></a>
            <a href="logout.php" class="logout"><i class='bx bx-log-out'></i></a>
        </div>
    </div>

    <div class="account-info">
        <div class="welcome">
            <h2><i class='bx bx-user-circle'></i></h2>
        </div>

        <!-- Add Product Button -->
        <div class="add-product">
            <button class="btn" onclick="openModal()">Post Item Here</button>

             <form method="GET" action="home.php" class="search-form">
                <div class="search-input-container">
                    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
                    <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                </div>
                <?php if (!empty($searchTerm)): ?>
                    <a href="home.php" class="clear-search">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h2>Create a Post</h2>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="home.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" id="item" name="item" placeholder="Item" required>
                    <input type="number" id="price" name="price" placeholder="Price" required>
                    <input type="text" id="address" name="address" placeholder="Address" required>
                    <textarea id="details" name="details" rows="3" placeholder="Details" required></textarea>
                </div>

                <label>Upload Photos:</label>
                <div id="photo-container">
                    <button type="button" class="file-button" onclick="document.getElementById('file-upload').click();">
                        <i class='bx bx-image'></i>
                    </button>
                    <input type="file" id="file-upload" class="file-input" name="photos[]" accept="image/*">
                    <button type="button" class="file-button" onclick="addPhotoInput()"><i class='bx bx-plus'></i></button>  
                </div>
                <div id="photo-preview-container"></div>

                <button type="submit" class="post-button">Post Product</button>
            </form>
        </div>
    </div>

    <!-- Chat box (hidden by default) -->
    <div class="chat-box" id="chat-box" style="display: none;">
        <div class="chat-box-header">
            <h2 id="chat-user-name"></h2>
            <button class="close-btn" onclick="closeChat()">✖</button>
        </div>
        <div class="chat-box-body" id="chat-box-body">
            <!-- Chat messages will be loaded here -->
        </div>
        <form class="chat-form" id="chat-form">
            <input type="hidden" id="sender" value="<?php echo $username; ?>">
            <input type="hidden" id="receiver">
            <input type="text" id="message" placeholder="Type your message..." required>
            <button type="submit"><i class='bx bxs-send'></i></button>
        </form>
        <div id="typing-indicator" style="color: gray; margin-top: 5px;"></div>
    </div>

    <div class="product-list">
        <h2>Preloved Items <?php if (!empty($searchTerm)) echo "- Search Results for: \"" . htmlspecialchars($searchTerm) . "\""; ?></h2>
        
        <div class="product-grid">
        <?php
        // Build search query
        $sql = "SELECT p.id, p.item, p.price, p.address, p.details, p.photo, p.likes, p.reports, p.status, u.username 
                FROM products p 
                JOIN users u ON p.username = u.username
                WHERE (p.status = 'available' OR p.status IS NULL OR p.status = 'sold')";
        
        if (!empty($searchTerm)) {
            $sql .= " AND (p.item LIKE ? OR p.details LIKE ? OR p.address LIKE ?)";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($searchTerm)) {
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $productId = $row['id'];
                $postedBy = htmlspecialchars($row['username']);
                $status = $row['status'] ?? 'available';
        ?>
        <div class="product-item <?php echo $status === 'sold' ? 'sold-item' : ''; ?>">
            <div class="product-header">
                <div class="product-posted-by">
                    <i class='bx bx-user-circle'></i> Posted by: <?php echo $postedBy; ?>
                </div>
                
                <div class="product-actions-header">
                    <?php if ($postedBy === $username && $status === 'available'): ?>
                        <div class="owner-actions">
                            <!-- Archive Button -->
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to archive this item?');">
                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                <input type="hidden" name="product_username" value="<?php echo $postedBy; ?>">
                                <button type="submit" name="archive_product" class="archive-btn" title="Archive Item">
                                    <i class='bx bx-archive'></i> Archive
                                </button>
                            </form>
                            
                            <!-- Sold Button -->
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Mark this item as sold?');">
                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                <input type="hidden" name="product_username" value="<?php echo $postedBy; ?>">
                                <button type="submit" name="mark_sold" class="sold-btn" title="Mark as Sold">
                                    <i class='bx bx-check-circle'></i> Mark Sold
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <a href="#" class="report-product" data-id="<?php echo $productId; ?>" data-user="<?php echo $postedBy; ?>">
                        <i class='bx bx-dots-horizontal-rounded'></i>
                    </a>
                </div>
            </div>
            
            <?php if ($status === 'sold'): ?>
                <div class="sold-overlay">
                    <span class="sold-badge">SOLD</span>
                </div>
            <?php endif; ?>
            
            <div class="product-info">
                <div class="product-title">Item: <?php echo htmlspecialchars($row['item']); ?></div>
                <div class="product-price">₱<?php echo number_format($row['price'], 2); ?></div>
                <div class="product-address">Address: <?php echo htmlspecialchars($row['address']); ?></div>
                
                <?php if (!empty($row['photo']) && $row['photo'] !== 'placeholder.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="<?php echo htmlspecialchars($row['item']); ?>" 
                         onerror="this.onerror=null; this.src='placeholder.jpg';">
                <?php else: ?>
                    <img src="placeholder.jpg" alt="No image available">
                <?php endif; ?>
                
                <div class="product-details">Details: <?php echo htmlspecialchars($row['details']); ?></div>
                
                <div class="product-actions">
                    <button class="like-btn" data-id="<?php echo $productId; ?>" <?php echo $status === 'sold' ? 'disabled' : ''; ?>>
                        <i class='bx bx-heart' style='color:black'></i>
                        <span class="like-count"><?php echo $row['likes']; ?></span>
                    </button>
                    <a href="#" class="select-user" data-user="<?php echo $postedBy; ?>" <?php echo $status === 'sold' ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                        <i class='bx bx-message-rounded'></i>
                    </a>
                    <button class="comment-btn" data-id="<?php echo $productId; ?>" <?php echo $status === 'sold' ? 'disabled' : ''; ?>>
                        <i class='bx bx-comment' style='color:#000'></i>
                    </button>
                    <a href="view_product_comments.php?product_id=<?php echo $productId; ?>" class="view-comments-btn">
                        <i class='bx bx-show'></i>
                    </a>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            if (!empty($searchTerm)) {
                echo "<p>No items found matching your search.</p>";
            } else {
                echo "<p>No products added yet.</p>";
            }
        }
        ?>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById("addProductModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("addProductModal").style.display = "none";
    }

    window.onclick = function(event) {
        var modal = document.getElementById("addProductModal");
        if (event.target == modal) {
            closeModal();
        }
    };
    
    // Add event listener to the file input
    document.getElementById('file-upload').addEventListener('change', function(e) {
        showImagePreview(e.target);
    });
    
    function showImagePreview(fileInput) {
        if (fileInput.files && fileInput.files[0]) {
            const previewContainer = document.getElementById('photo-preview-container');
            if (previewContainer) {
                previewContainer.innerHTML = ''; // Clear previous previews
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'photo-preview';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-img';
                    
                    previewDiv.appendChild(img);
                    previewContainer.appendChild(previewDiv);
                }
                
                reader.readAsDataURL(fileInput.files[0]);
            }
        }
    }

    function addPhotoInput() {
        var photoContainer = document.getElementById("photo-container");
        var newInput = document.createElement("input");
        newInput.type = "file";
        newInput.name = "photos[]";
        newInput.accept = "image/*";
        newInput.required = false;
        photoContainer.appendChild(newInput);
    }

    // Report product functionality
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".report-product").forEach(button => {
            button.addEventListener("click", function() {
                const productId = this.getAttribute("data-id");
                const username = this.getAttribute("data-user");

                if (confirm("Are you sure you want to report this product?")) {
                    fetch("report_product.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `product_id=${productId}&username=${username}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    });
                }
            });
        });
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Toggle messages dropdown
        $(".messages-btn").click(function() {
            $(".messages-list").toggle();
        });

        // Open chat box when user is selected
        $(".select-user").click(function() {
            var selectedUser = $(this).data("user");
            $("#chat-user-name").text(selectedUser);
            $("#receiver").val(selectedUser);
            $("#chat-box").show();
            fetchMessages();
        });

        // Like button functionality
        $(".like-btn").click(function() {
            var button = $(this);
            var productId = button.data("id");

            if (!button.prop('disabled')) {
                $.ajax({
                    url: "likeproduct.php",
                    type: "POST",
                    data: { product_id: productId },
                    success: function(response) {
                        if (response !== "error") {
                            button.find(".like-count").text(response);
                        } else {
                            alert("Error liking the product.");
                        }
                    }
                });
            }
        });

        // Comment button functionality
        $(".comment-btn").click(function() {
            if (!$(this).prop('disabled')) {
                var productId = $(this).data("id");
                var comment = prompt("Enter your comment:");

                if (comment) {
                    $.ajax({
                        url: "add_comment.php",
                        type: "POST",
                        data: { product_id: productId, comment: comment },
                        success: function(response) {
                            console.log("Server Response:", response);
                            if (response.trim() === "success") {
                                alert("Comment added successfully!");
                                location.reload();
                            } else {
                                alert("Error: " + response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log("AJAX Error:", error);
                        }
                    });
                }
            }
        });

        // Fetch messages
        function fetchMessages() {
            var sender = $("#sender").val();
            var receiver = $("#receiver").val();
            
            $.ajax({
                url: "fetch_messages.php",
                type: "POST",
                data: {sender: sender, receiver: receiver},
                success: function(data) {
                    $("#chat-box-body").html(data);
                    scrollChatToBottom();
                }
            });
        }

        // Scroll chat to bottom
        function scrollChatToBottom() {
            var chatBox = $("#chat-box-body");
            chatBox.scrollTop(chatBox.prop("scrollHeight"));
        }

        // Submit chat form
        $("#chat-form").submit(function(e) {
            e.preventDefault();
            var sender = $("#sender").val();
            var receiver = $("#receiver").val();
            var message = $("#message").val();

            $.ajax({
                url: "submit_message.php",
                type: "POST",
                data: {sender: sender, receiver: receiver, message: message},
                success: function() {
                    $("#message").val('');
                    fetchMessages();
                }
            });
        });

        // Close chat box
        window.closeChat = function() {
            $("#chat-box").hide();
        };
    });
</script>

<style>
/* Ensure modal fills screen background */
.modal {
    display: none; /* Or block if you want to show it */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Enables scrolling */
    background-color: rgba(0, 0, 0, 0.5); /* Dimmed background */
    z-index: 1000;
}

/* Scrollable modal content */
.modal-content {
    background-color:rgb(216, 233, 245);
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh; /* Limit modal height */
    overflow-y: auto; /* Enables vertical scroll */
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* Center forms horizontally */
.form-button-group {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px; /* Space between buttons */
    margin-top: 20px;
}

/* Optional margin if needed on one of the forms */
.form-spacing {
    /* You can remove this if using gap */
    margin-right: 0;
}

/* Base button styles */
.archive-btn,
.sold-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    margin: 3px 2px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    color: #fff;
}

/* Archive Button Style */
.archive-btn {
    background-color: #ff6b6b;
}

.archive-btn:hover {
    background-color: #e55a5a;
    transform: scale(1.03);
}

/* Sold Button Style */
.sold-btn {
    background-color: #4caf50;
}

.sold-btn:hover {
    background-color: #3e8e41;
    transform: scale(1.03);
}

/* Optional: Icon styles */
.archive-btn i,
.sold-btn i {
    font-size: 16px;
}


/* Chat Box Styles */
.chat-box {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    height: 400px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.chat-box-header {
    background: #508af5;
    color: white;
    padding: 15px;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-box-header h2 {
    margin: 0;
    font-size: 16px;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
}

.close-btn:hover {
    background: rgba(255,255,255,0.1);
}

.chat-box-body {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f9f9f9;
}

.chat-form {
    display: flex;
    padding: 15px;
    border-top: 1px solid #eee;
    background: white;
    border-radius: 0 0 10px 10px;
}

.chat-form input[type="text"] {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 20px;
    outline: none;
    margin-right: 10px;
}

.chat-form button {
    background: #508af5;
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-form button:hover {
    background: #508af5;
}

/* Messages Dropdown */
.messages-dropdown {
    position: relative;
}

.messages-btn {
    background: none;
    border: none;
    color: #666;
    font-size: 30px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
}


</style>