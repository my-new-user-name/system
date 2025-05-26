
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

// Handle restore button action
if (isset($_POST['restore_product']) && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $productUsername = mysqli_real_escape_string($conn, $_POST['product_username']);
    
    // Check if the current user is the owner of the product
    if ($productUsername === $username) {
        $updateSql = "UPDATE products SET status = 'available' WHERE id = ? AND username = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("is", $productId, $username);
        
        if ($updateStmt->execute()) {
            header("Location: archive.php");
            exit();
        }
    }
}

// Handle permanent delete action
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $productUsername = mysqli_real_escape_string($conn, $_POST['product_username']);
    
    // Check if the current user is the owner of the product
    if ($productUsername === $username) {
        // First get the photo path to delete the file
        $getPhotoSql = "SELECT photo FROM products WHERE id = ? AND username = ?";
        $getPhotoStmt = $conn->prepare($getPhotoSql);
        $getPhotoStmt->bind_param("is", $productId, $username);
        $getPhotoStmt->execute();
        $photoResult = $getPhotoStmt->get_result();
        
        if ($photoRow = $photoResult->fetch_assoc()) {
            $photoPath = $photoRow['photo'];
            
            // Delete the product from database
            $deleteSql = "DELETE FROM products WHERE id = ? AND username = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("is", $productId, $username);
            
            if ($deleteStmt->execute()) {
                // Delete the photo file if it exists and is not placeholder
                if ($photoPath && $photoPath !== 'placeholder.jpg' && file_exists($photoPath)) {
                    unlink($photoPath);
                }
                header("Location: archive.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Items - Happy Thrift</title>
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Happy Thrift</h1>
       
        <div class="header-right">
            <a href="home.php" class="home"><i class='bx bx-home'></i></a>
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
            <a href="sold.php" class="sold-link"><i class='bx bx-check-circle'></i></a>
            <a href="logout.php" class="logout"><i class='bx bx-log-out'></i></a>
        </div>
    </div>

 <div class="account-info">
        <div class="welcome">
            <h2><i class='bx bx-archive'></i> Archived Items</h2>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="archive.php" class="search-form">
                <div class="search-input-container">
                    <input type="text" name="search" placeholder="Search archived items..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
                    <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                </div>
                <?php if (!empty($searchTerm)): ?>
                    <a href="archive.php" class="clear-search">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Navigation Links -->
        <div class="archive-navigation">
            <a href="home.php" class="nav-link">
                <i class='bx bx-home'></i> Active Items
            </a>
            <a href="sold.php" class="nav-link">
                <i class='bx bx-check-circle'></i> Sold Items
            </a>
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
        <h2>Your Archived Items <?php if (!empty($searchTerm)) echo "- Search Results for: \"" . htmlspecialchars($searchTerm) . "\""; ?></h2>
        
        <div class="product-grid">
        <?php
        // Build search query for archived items owned by current user
        $sql = "SELECT p.id, p.item, p.price, p.address, p.details, p.photo, p.likes, p.reports, p.status, u.username 
                FROM products p 
                JOIN users u ON p.username = u.username
                WHERE p.status = 'archived' AND p.username = ?";
        
        if (!empty($searchTerm)) {
            $sql .= " AND (p.item LIKE ? OR p.details LIKE ? OR p.address LIKE ?)";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($searchTerm)) {
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("ssss", $username, $searchPattern, $searchPattern, $searchPattern);
        } else {
            $stmt->bind_param("s", $username);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $productId = $row['id'];
                $postedBy = htmlspecialchars($row['username']);
                $status = $row['status'] ?? 'archived';
        ?>
        <div class="product-item archived-item">
            <div class="product-header">
                <div class="product-posted-by">
                    <i class='bx bx-user-circle'></i> Posted by: You
                </div>
                
                <div class="product-actions-header">
                    <div class="owner-actions">
                        <!-- Restore Button -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to restore this item to active status?');">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <input type="hidden" name="product_username" value="<?php echo $postedBy; ?>">
                            <button type="submit" name="restore_product" class="restore-btn" title="Restore Item">
                                <i class='bx bx-reset'></i> Restore
                            </button>
                        </form>
                        
                        <!-- Permanent Delete Button -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this item? This action cannot be undone!');">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <input type="hidden" name="product_username" value="<?php echo $postedBy; ?>">
                            <button type="submit" name="delete_product" class="delete-btn" title="Delete Permanently">
                                <i class='bx bx-trash'></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="archived-overlay">
                <span class="archived-badge">ARCHIVED</span>
            </div>
            
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
                
                <div class="product-stats">
                    <div class="stat-item">
                        <i class='bx bx-heart'></i>
                        <span><?php echo $row['likes']; ?> likes</span>
                    </div>
                    <div class="stat-item">
                        <i class='bx bx-time'></i>
                        <span>Archived</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            if (!empty($searchTerm)) {
                echo "<p>No archived items found matching your search.</p>";
            } else {
                echo "<p>You have no archived items. <a href='home.php'>Go back to active items</a></p>";
            }
        }
        ?>
        </div>
    </div>
</div>

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
.messages-btn {
    background: none;
    border: none;
    font-size: 30px;
    color: #000; /* Set color to black */
    cursor: pointer;
    transition: color 0.3s ease, transform 0.2s ease;
    margin-top: 5px;
    font-size: 30px;
}

.messages-btn:hover {
    color: #2790f1; /* Blue on hover */
    transform: scale(1.1);
}

.account-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap; /* allows items to stack on smaller screens */
    gap: 10px;
    padding: 10px 20px;
    background-color: #fff;
}

.welcome h2 {
    margin: 0;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-container {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

/* Input + button container */
.search-input-container {
    display: flex;
    align-items: center;
    border: 1px solid #ccc;
    border-radius: 20px;
    overflow: hidden;
    max-width: 600px;
    width: 160%;
    margin-top: 12px;
}

/* Search input */
.search-input {
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    width: 100%;
    outline: none;
}

/* Search icon button */
.search-btn {
    background-color: #729fbd;
    color: #fff;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    background-color: #91b0c5;
}

/* Clear search link */
.clear-search {
    margin-left: 10px;
    color: #729fbd;
    font-size: 14px;
    text-decoration: none;
    white-space: nowrap;
}

.clear-search:hover {
    text-decoration: underline;
}

.archive-navigation {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    padding: 10px 0;
    border-top: 1px solid #ddd;
    padding-top: 5px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    background-color: #f5f7fa;
    color: #333;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 16px;
    transition: background-color 0.3s, color 0.3s;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.nav-link i {
    font-size: 18px;
}

.nav-link:hover {
    background-color: #e1ecf4;
    color: #007bff;
}

.product-actions-header {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-top: 20px;
}

.owner-actions {
    display: flex;
    gap: 10px;
}

.restore-btn,
.delete-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.restore-btn {
    background-color: #e0f7ea;
    color: #2e7d32;
}

.restore-btn:hover {
    background-color: #c4ecd7;
    color: #1b5e20;
}

.delete-btn {
    background-color: #fdecea;
    color: #d32f2f;
}

.delete-btn:hover {
    background-color: #f9d5d3;
    color: #b71c1c;
}

.restore-btn i,
.delete-btn i {
    font-size: 18px;
}

.no-results-message,
.empty-archive-message {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px 20px;
    border-radius: 8px;
    font-size: 16px;
    color: #555;
    margin-top: 20px;
    max-width: 600px;
}

.no-results-message a,
.empty-archive-message a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.no-results-message a:hover,
.empty-archive-message a:hover {
    text-decoration: underline;
}

