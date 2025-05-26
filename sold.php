
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sold Items - Happy Thrift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<!-- Glassmorphism Background -->
<div class="bg-pattern"></div>

<div class="container">
    <!-- Modern Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class='bx bx-store-alt'></i>
                </div>
                <div class="logo-text">
                    <h1>Happy Thrift</h1>
                    <span class="subtitle">Sold Items</span>
                </div>
            </div>
            
            <nav class="nav-icons">
                <a href="home.php" class="nav-btn" title="Home">
                    <i class='bx bx-home'></i>
                    <span>Home</span>
                </a>
                <a href="userprofile.php" class="nav-btn" title="Profile">
                    <i class='bx bx-user-circle'></i>
                    <span>Profile</span>
                </a>
                <a href="message.php" class="nav-btn" title="Messages">
                    <i class='bx bxl-messenger'></i>
                    <span>Messages</span>
                </a>
                
                <!-- Enhanced Messages Dropdown -->
                <div class="messages-dropdown">
                    <button class="nav-btn messages-btn">
                        <i class='bx bx-envelope'></i>
                        <span>Chat</span>
                    </button>
                    <div class="messages-list">
                        <div class="dropdown-header">
                            <h3>Start Conversation</h3>
                        </div>
                        <div class="users-list">
                            <?php 
                            $sql = "SELECT username FROM users WHERE username != ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $username);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                $user = ucfirst($row['username']);
                                echo "<div class='user-item select-user' data-user='" . htmlspecialchars($row['username']) . "'>";
                                echo "<div class='user-avatar'><i class='bx bx-user-circle'></i></div>";
                                echo "<span class='user-name'>$user</span>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <a href="edit_profile.php" class="nav-btn" title="Settings">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
                <a href="archive.php" class="nav-btn" title="Archive">
                    <i class='bx bx-archive'></i>
                    <span>Archive</span>
                </a>
                <a href="logout.php" class="nav-btn logout-btn" title="Logout">
                    <i class='bx bx-log-out'></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">
                <i class='bx bx-check-circle'></i>
            </div>
            <h2>Successfully Sold Items</h2>
            <p>Browse through all completed transactions and success stories</p>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <section class="controls-section">
        <div class="search-container">
            <form method="GET" action="sold.php" class="search-form">
                <div class="search-wrapper">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" name="search" placeholder="Search sold items, descriptions, locations..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
                    <button type="submit" class="search-btn">
                        <i class='bx bx-search'></i>
                    </button>
                </div>
                <?php if (!empty($searchTerm)): ?>
                    <a href="sold.php" class="clear-search">
                        <i class='bx bx-x'></i> Clear Search
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="filter-tabs">
            <a href="sold.php" class="filter-tab <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">
                <i class='bx bx-store'></i>
                <span>All Sold Items</span>
            </a>
            <a href="sold.php?filter=my_items" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'my_items') ? 'active' : ''; ?>">
                <i class='bx bx-user'></i>
                <span>My Sold Items</span>
            </a>
        </div>
    </section>

    <!-- Modern Chat Interface -->
    <div class="chat-overlay" id="chat-overlay">
        <div class="chat-container" id="chat-box">
            <div class="chat-header">
                <div class="chat-user-info">
                    <div class="chat-avatar">
                        <i class='bx bx-user-circle'></i>
                    </div>
                    <div class="chat-user-details">
                        <h3 id="chat-user-name"></h3>
                        <span class="online-status">Online</span>
                    </div>
                </div>
                <button class="chat-close-btn" onclick="closeChat()">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            
            <div class="chat-messages" id="chat-box-body">
                <!-- Messages will be loaded here -->
            </div>
            
            <form class="chat-input-container" id="chat-form">
                <input type="hidden" id="sender" value="<?php echo $username; ?>">
                <input type="hidden" id="receiver">
                <div class="message-input-wrapper">
                    <input type="text" id="message" placeholder="Type your message..." required>
                    <button type="submit" class="send-btn">
                        <i class='bx bxs-send'></i>
                    </button>
                </div>
            </form>
            <div id="typing-indicator" class="typing-indicator"></div>
        </div>
    </div>

    <!-- Products Grid -->
    <main class="products-section">
        <div class="section-header">
            <div class="results-info">
                <h2>
                    <i class='bx bx-check-circle'></i>
                    Sold Items
                    <?php if (!empty($searchTerm)) echo " - \"" . htmlspecialchars($searchTerm) . "\""; ?>
                    <?php if (isset($_GET['filter']) && $_GET['filter'] === 'my_items') echo " - My Items"; ?>
                </h2>
            </div>
        </div>
        
        <div class="products-grid">
        <?php
        // Build search query for sold items
        $sql = "SELECT p.id, p.item, p.price, p.address, p.details, p.photo, p.likes, p.reports, p.status, p.created_at, u.username 
                FROM products p 
                JOIN users u ON p.username = u.username
                WHERE p.status = 'sold'";
        
        // Add filter for user's own items
        if (isset($_GET['filter']) && $_GET['filter'] === 'my_items') {
            $sql .= " AND p.username = ?";
        }
        
        // Add search functionality
        if (!empty($searchTerm)) {
            $sql .= " AND (p.item LIKE ? OR p.details LIKE ? OR p.address LIKE ?)";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters based on filters
        if (isset($_GET['filter']) && $_GET['filter'] === 'my_items' && !empty($searchTerm)) {
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("ssss", $username, $searchPattern, $searchPattern, $searchPattern);
        } elseif (isset($_GET['filter']) && $_GET['filter'] === 'my_items') {
            $stmt->bind_param("s", $username);
        } elseif (!empty($searchTerm)) {
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $productId = $row['id'];
                $postedBy = htmlspecialchars($row['username']);
                $soldDate = new DateTime($row['created_at']);
                $now = new DateTime();
                $interval = $now->diff($soldDate);
                
                // Format time ago
                if ($interval->days > 0) {
                    $timeAgo = $interval->days . " day" . ($interval->days > 1 ? "s" : "") . " ago";
                } elseif ($interval->h > 0) {
                    $timeAgo = $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
                } else {
                    $timeAgo = $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
                }
        ?>
        <article class="product-card sold-card">
            <div class="card-header">
                <div class="seller-info">
                    <div class="seller-avatar">
                        <i class='bx bx-user-circle'></i>
                    </div>
                    <div class="seller-details">
                        <span class="seller-name"><?php echo $postedBy; ?></span>
                        <span class="post-time">Sold <?php echo $timeAgo; ?></span>
                    </div>
                </div>
                <div class="sold-badge">
                    <i class='bx bx-check-circle'></i>
                    <span>SOLD</span>
                </div>
            </div>
            
            <div class="card-image">
                <?php if (!empty($row['photo']) && $row['photo'] !== 'placeholder.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="<?php echo htmlspecialchars($row['item']); ?>" 
                         onerror="this.onerror=null; this.src='placeholder.jpg';">
                <?php else: ?>
                    <img src="placeholder.jpg" alt="No image available">
                <?php endif; ?>
                <div class="image-overlay">
                    <div class="overlay-content">
                        <i class='bx bx-check-circle'></i>
                        <span>Successfully Sold</span>
                    </div>
                </div>
            </div>
            
            <div class="card-content">
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($row['item']); ?></h3>
                    <div class="price-tag">₱<?php echo number_format($row['price'], 2); ?></div>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class='bx bx-map'></i>
                        <span><?php echo htmlspecialchars($row['address']); ?></span>
                    </div>
                </div>
                
                <div class="product-description">
                    <?php echo htmlspecialchars($row['details']); ?>
                </div>
                
                <div class="card-actions">
                    <div class="likes-info">
                        <i class='bx bx-heart'></i>
                        <span><?php echo $row['likes']; ?> likes</span>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="action-btn contact-btn select-user" data-user="<?php echo $postedBy; ?>">
                            <i class='bx bx-message-rounded'></i>
                            <span>Contact</span>
                        </button>
                        <a href="view_product_comments.php?product_id=<?php echo $productId; ?>" class="action-btn view-btn">
                            <i class='bx bx-comment-detail'></i>
                            <span>Comments</span>
                        </a>
                    </div>
                </div>
            </div>
        </article>
        <?php
            }
        } else {
            $noResultsMessage = "No sold items found";
            if (!empty($searchTerm)) {
                $noResultsMessage .= " matching your search";
            }
            if (isset($_GET['filter']) && $_GET['filter'] === 'my_items') {
                $noResultsMessage = "You haven't sold any items yet";
            }
            echo "<div class='empty-state'>";
            echo "<div class='empty-icon'><i class='bx bx-package'></i></div>";
            echo "<h3>$noResultsMessage</h3>";
            if (isset($_GET['filter']) && $_GET['filter'] === 'my_items') {
                echo "<p>Start selling your items to see them here when they're sold.</p>";
                echo "<a href='home.php' class='cta-btn'>Post Your First Item</a>";
            } else {
                echo "<p>Check back later or browse available items.</p>";
                echo "<a href='home.php' class='cta-btn'>Browse Available Items</a>";
            }
            echo "</div>";
        }
        ?>
        </div>
    </main>
</div>

<script>
$(document).ready(function() {
    // Enhanced messages dropdown
    $(".messages-btn").click(function(e) {
        e.preventDefault();
        $(".messages-list").toggle();
    });

    // Close dropdown when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.messages-dropdown').length) {
            $(".messages-list").hide();
        }
    });

    // Open chat with smooth animation
    $(".select-user").click(function(e) {
        e.preventDefault();
        var selectedUser = $(this).data("user");
        $("#chat-user-name").text(selectedUser);
        $("#receiver").val(selectedUser);
        $("#chat-overlay").addClass("active");
        $(".messages-list").hide();
        fetchMessages();
    });

    // Fetch messages with modern styling
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

    // Smooth scroll to bottom
    function scrollChatToBottom() {
        var chatBox = $("#chat-box-body");
        chatBox.animate({
            scrollTop: chatBox.prop("scrollHeight")
        }, 300);
    }

    // Enhanced chat form submission
    $("#chat-form").submit(function(e) {
        e.preventDefault();
        var sender = $("#sender").val();
        var receiver = $("#receiver").val();
        var message = $("#message").val().trim();

        if (message === '') return;

        // Add sending animation
        var messageInput = $("#message");
        messageInput.prop('disabled', true);
        
        $.ajax({
            url: "submit_message.php",
            type: "POST",
            data: {sender: sender, receiver: receiver, message: message},
            success: function() {
                messageInput.val('').prop('disabled', false).focus();
                fetchMessages();
            },
            error: function() {
                messageInput.prop('disabled', false);
                alert('Failed to send message. Please try again.');
            }
        });
    });

    // Enhanced close chat function
    window.closeChat = function() {
        $("#chat-overlay").removeClass("active");
    };

    // Auto-refresh messages every 3 seconds when chat is open
    setInterval(function() {
        if ($("#chat-overlay").hasClass("active")) {
            fetchMessages();
        }
    }, 3000);
});
</script>

<style>
/* Modern CSS Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    line-height: 1.6;
    color: #2d3748;
    position: relative;
}

/* Animated Background Pattern */
.bg-pattern {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background-image: 
        radial-gradient(circle at 25% 25%, #ffffff 2px, transparent 2px),
        radial-gradient(circle at 75% 75%, #ffffff 2px, transparent 2px);
    background-size: 60px 60px;
    background-position: 0 0, 30px 30px;
    animation: patternMove 20s linear infinite;
    z-index: -1;
}

@keyframes patternMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(60px, 60px); }
}

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Modern Header */
.header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.logo-text h1 {
    font-size: 28px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo-text .subtitle {
    font-size: 14px;
    color: #718096;
    font-weight: 500;
}

.nav-icons {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 16px;
    text-decoration: none;
    color: #4a5568;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.nav-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea, #764ba2);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: -1;
}

.nav-btn:hover::before {
    opacity: 0.1;
}

.nav-btn:hover {
    color: #667eea;
    transform: translateY(-2px);
}

.nav-btn i {
    font-size: 20px;
}

.nav-btn span {
    font-size: 12px;
    font-weight: 500;
}

.logout-btn:hover {
    color: #e53e3e;
}

.logout-btn:hover::before {
    background: #e53e3e;
}

/* Messages Dropdown */
.messages-dropdown {
    position: relative;
}

.messages-list {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    min-width: 280px;
    display: none;
    z-index: 1000;
    overflow: hidden;
}

.dropdown-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 16px 20px;
    text-align: center;
}

.dropdown-header h3 {
    font-size: 16px;
    font-weight: 600;
}

.users-list {
    max-height: 300px;
    overflow-y: auto;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid #f7fafc;
}

.user-item:hover {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.user-name {
    font-weight: 500;
    color: #2d3748;
}

/* Hero Section */
.hero-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    margin: 20px 0;
    border-radius: 24px;
    padding: 60px 40px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.hero-content .hero-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #48bb78, #38a169);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 40px;
    color: white;
    box-shadow: 0 8px 32px rgba(72, 187, 120, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.hero-content h2 {
    font-size: 32px;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 12px;
}

.hero-content p {
    font-size: 18px;
    color: #718096;
    max-width: 600px;
    margin: 0 auto;
}

/* Controls Section */
.controls-section {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin: 32px 0;
}

.search-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: #f7fafc;
    border-radius: 16px;
    padding: 4px;
    transition: all 0.3s;
}

.search-wrapper:focus-within {
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.search-icon {
    position: absolute;
    left: 16px;
    color: #a0aec0;
    font-size: 20px;
    z-index: 2;
}

.search-input {
    flex: 1;
    padding: 16px 16px 16px 50px;
    border: none;
    background: transparent;
    font-size: 16px;
    color: #2d3748;
    outline: none;
}

.search-input::placeholder {
    color: #a0aec0;
}

.search-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.clear-search {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    color: #e53e3e;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.clear-search:hover {
    color: #c53030;
}

.filter-tabs {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 32px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 2px solid transparent;
    border-radius: 16px;
    text-decoration: none;
    color: #4a5568;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.filter-tab::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea, #764ba2);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: -1;
}

.filter-tab:hover::before,
.filter-tab.active::before {
    opacity: 0.1;
}

.filter-tab:hover,
.filter-tab.active {
    color: #667eea;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
}

.filter-tab.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
}

.filter-tab.active::before {
    opacity: 0;
}

.filter-tab i {
    font-size: 18px;
}

/* Chat Overlay */
.chat-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-overlay.active {
    opacity: 1;
    visibility: visible;
}

.chat-container {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    height: 600px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    transform: scale(0.9) translateY(20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-overlay.active .chat-container {
    transform: scale(1) translateY(0);
}

.chat-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 20px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-avatar {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.chat-user-details h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 4px;
}

.online-status {
    font-size: 12px;
    opacity: 0.8;
}

.chat-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
}

.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: #f8fafc;
}

.chat-input-container {
    padding: 20px;
    background: white;
    border-radius: 0 0 20px 20px;
    border-top: 1px solid #e2e8f0;
}

.message-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.message-input-wrapper input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    outline: none;
    transition: all 0.3s;
}

.message-input-wrapper input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.send-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
}

.send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.typing-indicator {
    padding: 8px 0;
    font-size: 12px;
    color: #718096;
    font-style: italic;
}

/* Products Section */
.products-section {
    margin: 32px 0;
}

.section-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.results-info h2 {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 600;
    color: #2d3748;
}

.results-info h2 i {
    color: #48bb78;
    font-size: 28px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

/* Product Cards */
.product-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

.sold-card {
    border: 2px solid #48bb78;
    position: relative;
}

.sold-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(72, 187, 120, 0.05), rgba(56, 161, 105, 0.05));
    z-index: 1;
    pointer-events: none;
}

.card-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f7fafc;
    position: relative;
    z-index: 2;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.seller-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.seller-details {
    display: flex;
    flex-direction: column;
}

.seller-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
}

.post-time {
    font-size: 12px;
    color: #718096;
}

.sold-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #48bb78, #38a169);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
    animation: soldBadgePulse 2s infinite;
}

@keyframes soldBadgePulse {
    0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4); }
}

.card-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .card-image img {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(72, 187, 120, 0.8), rgba(56, 161, 105, 0.8));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.sold-card:hover .image-overlay {
    opacity: 1;
}

.overlay-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: white;
    text-align: center;
}

.overlay-content i {
    font-size: 48px;
    animation: checkBounce 1s ease-in-out;
}

@keyframes checkBounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.overlay-content span {
    font-size: 16px;
    font-weight: 600;
}

.card-content {
    padding: 24px;
    position: relative;
    z-index: 2;
}

.product-info {
    margin-bottom: 16px;
}

.product-title {
    font-size: 20px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    line-height: 1.3;
}

.price-tag {
    font-size: 24px;
    font-weight: 700;
    color: #48bb78;
    display: flex;
    align-items: center;
    gap: 4px;
}

.product-meta {
    margin-bottom: 16px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #718096;
    font-size: 14px;
    margin-bottom: 8px;
}

.meta-item i {
    color: #a0aec0;
    font-size: 16px;
}

.product-description {
    color: #4a5568;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #f7fafc;
}

.likes-info {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #e53e3e;
    font-size: 14px;
    font-weight: 500;
}

.likes-info i {
    font-size: 18px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-decoration: none;
    color: #4a5568;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
    background: white;
    cursor: pointer;
}

.action-btn:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.contact-btn:hover {
    border-color: #48bb78;
    color: #48bb78;
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.2);
}

.action-btn i {
    font-size: 16px;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 40px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.empty-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #cbd5e0, #a0aec0);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 48px;
    color: white;
    opacity: 0.7;
}

.empty-state h3 {
    font-size: 24px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 12px;
}

.empty-state p {
    font-size: 16px;
    color: #718096;
    margin-bottom: 24px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 16px 32px;
    border-radius: 16px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.cta-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 0 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        padding: 16px 0;
    }
    
    .nav-icons {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .nav-btn span {
        display: none;
    }
    
    .hero-section {
        padding: 40px 20px;
        margin: 16px 0;
    }
    
    .hero-content h2 {
        font-size: 24px;
    }
    
    .hero-content p {
        font-size: 16px;
    }
    
    .controls-section {
        gap: 16px;
        margin: 24px 0;
    }
    
    .filter-tabs {
        flex-direction: column;
        align-items: center;
    }
    
    .filter-tab {
        width: 100%;
        max-width: 280px;
        justify-content: center;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .chat-container {
        width: 95%;
        height: 80vh;
        margin: 20px;
    }
    
    .card-actions {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .messages-list {
        right: -20px;
        min-width: 250px;
    }
}

@media (max-width: 480px) {
    .logo-text h1 {
        font-size: 24px;
    }
    
    .hero-content .hero-icon {
        width: 60px;
        height: 60px;
        font-size: 30px;
    }
    
    .hero-content h2 {
        font-size: 20px;
    }
    
    .search-input {
        font-size: 14px;
        padding: 14px 14px 14px 45px;
    }
    
    .product-title {
        font-size: 18px;
    }
    
    .price-tag {
        font-size: 20px;
    }
    
    .card-image {
        height: 200px;
    }
}

/* Loading Animation */
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

.loading-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 1000px 100%;
    animation: shimmer 2s infinite;
}

/* Scroll to top button */
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
    z-index: 1000;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

/* Print Styles */
@media print {
    .header,
    .hero-section,
    .controls-section,
    .chat-overlay,
    .scroll-to-top {
        display: none !important;
    }
    
    .product-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

</body>
</html>
