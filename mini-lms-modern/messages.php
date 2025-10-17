<?php
// File: messages.php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$current_page = isset($_GET['page']) ? $_GET['page'] : 'inbox';
$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $receiver_id = intval($_POST['receiver_id']);
        $subject = $conn->real_escape_string($_POST['subject']);
        $message = $conn->real_escape_string($_POST['message']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, parent_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $user_id, $receiver_id, $subject, $message, $parent_id);
        
        if ($stmt->execute()) {
            $success = "Message sent successfully!";
        } else {
            $error = "Error sending message: " . $stmt->error;
        }
    }
    
    if (isset($_POST['mark_read']) && $message_id > 0) {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();
    }
    
    if (isset($_POST['delete_message']) && $message_id > 0) {
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->bind_param("iii", $message_id, $user_id, $user_id);
        $stmt->execute();
    }
}

// Get messages based on current page
if ($current_page === 'inbox') {
    $stmt = $conn->prepare("
        SELECT m.*, u.name as sender_name, u.role as sender_role 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
} elseif ($current_page === 'sent') {
    $stmt = $conn->prepare("
        SELECT m.*, u.name as receiver_name, u.role as receiver_role 
        FROM messages m 
        JOIN users u ON m.receiver_id = u.id 
        WHERE m.sender_id = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$messages = $stmt->get_result();

// Get specific message for view
$current_message = null;
if ($message_id > 0) {
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.name as sender_name, sender.role as sender_role,
               receiver.name as receiver_name, receiver.role as receiver_role
        FROM messages m 
        JOIN users sender ON m.sender_id = sender.id 
        JOIN users receiver ON m.receiver_id = receiver.id 
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->bind_param("iii", $message_id, $user_id, $user_id);
    $stmt->execute();
    $current_message = $stmt->get_result()->fetch_assoc();
    
    // Mark as read if viewing and it's in inbox
    if ($current_message && $current_message['receiver_id'] == $user_id && !$current_message['is_read']) {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    }
}

// Get conversation thread
$conversation = [];
if ($current_message) {
    $parent_id = $current_message['parent_id'] ?: $current_message['id'];
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.name as sender_name, sender.role as sender_role,
               receiver.name as receiver_name, receiver.role as receiver_role
        FROM messages m 
        JOIN users sender ON m.sender_id = sender.id 
        JOIN users receiver ON m.receiver_id = receiver.id 
        WHERE m.id = ? OR m.parent_id = ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("ii", $parent_id, $parent_id);
    $stmt->execute();
    $conversation = $stmt->get_result();
}

// Get users for new message (excluding current user)
$users_result = $conn->query("SELECT id, name, role FROM users WHERE id != $user_id ORDER BY name");

// Get unread count for sidebar display
$unread_count = getUnreadMessageCount($user_id, $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-item.unread {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }
        .message-item:hover {
            background-color: #f8fafc;
        }
        .conversation-bubble {
            max-width: 70%;
            margin: 10px 0;
            padding: 12px 16px;
            border-radius: 18px;
        }
        .sent-message {
            background: #3b82f6;
            color: white;
            margin-left: auto;
        }
        .received-message {
            background: #f1f5f9;
            color: #334155;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-2xl font-bold flex items-center">
                        <i class="fas fa-graduation-cap mr-2"></i> LMS
                    </a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="<?php echo $_SESSION['user']['role'] == 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'; ?>" 
                           class="hover:bg-blue-700 px-3 py-2 rounded">Dashboard</a>
                        <a href="browse_courses.php" class="hover:bg-blue-700 px-3 py-2 rounded">Courses</a>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user'])): ?>
                        <!-- Messages Icon with Notification Badge -->
                        <a href="messages.php" class="relative hover:bg-blue-700 p-2 rounded">
                            <i class="fas fa-envelope text-xl"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="relative group">
                            <button class="flex items-center space-x-2 hover:bg-blue-700 px-3 py-2 rounded">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                                <i class="fas fa-chevron-down text-sm"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 hidden group-hover:block z-50">
                                <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                                <a href="messages.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Messages</a>
                                <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="hover:bg-blue-700 px-3 py-2 rounded">Login</a>
                        <a href="register.php" class="bg-white text-blue-600 px-3 py-2 rounded hover:bg-gray-100">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-envelope mr-3"></i>Messages
                </h1>
                <p class="text-gray-600">Manage your conversations and messages</p>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <!-- Navigation -->
                        <div class="p-4 border-b border-gray-200">
                            <a href="?page=inbox" 
                               class="block w-full text-left px-4 py-3 rounded-lg mb-2 <?php echo $current_page === 'inbox' ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <i class="fas fa-inbox mr-3"></i>Inbox
                                <?php if ($current_page === 'inbox'): ?>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full ml-2">
                                        <?php echo $unread_count; ?> unread
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="?page=sent" 
                               class="block w-full text-left px-4 py-3 rounded-lg <?php echo $current_page === 'sent' ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <i class="fas fa-paper-plane mr-3"></i>Sent Messages
                            </a>
                        </div>

                        <!-- New Message Button -->
                        <div class="p-4">
                            <button onclick="openComposeModal()" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
                                <i class="fas fa-edit mr-2"></i>Compose Message
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <?php if ($message_id > 0 && $current_message): ?>
                        <!-- Message View -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <!-- Message Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800 mb-2">
                                            <?php echo htmlspecialchars($current_message['subject']); ?>
                                        </h2>
                                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                                            <span><i class="fas fa-user mr-1"></i> 
                                                From: <?php echo htmlspecialchars($current_message['sender_name']); ?>
                                                (<?php echo ucfirst($current_message['sender_role']); ?>)
                                            </span>
                                            <span><i class="fas fa-clock mr-1"></i> 
                                                <?php echo date('M j, Y g:i A', strtotime($current_message['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="mark_read" value="1">
                                            <button type="submit" class="text-gray-400 hover:text-gray-600 p-2">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                            <input type="hidden" name="delete_message" value="1">
                                            <button type="submit" class="text-gray-400 hover:text-red-600 p-2">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversation Thread -->
                            <div class="p-6">
                                <?php while ($msg = $conversation->fetch_assoc()): ?>
                                    <div class="conversation-bubble <?php echo $msg['sender_id'] == $user_id ? 'sent-message' : 'received-message'; ?>">
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                        <div class="message-time text-right">
                                            <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <!-- Reply Form -->
                            <div class="p-6 border-t border-gray-200">
                                <form method="POST">
                                    <input type="hidden" name="parent_id" value="<?php echo $current_message['parent_id'] ?: $current_message['id']; ?>">
                                    <input type="hidden" name="receiver_id" value="<?php echo $current_message['sender_id']; ?>">
                                    <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($current_message['subject']); ?>">
                                    
                                    <div class="mb-4">
                                        <textarea name="message" rows="4" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                  placeholder="Type your reply here..." required></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" name="send_message" 
                                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                                            <i class="fas fa-reply mr-2"></i>Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Messages List -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php echo $current_page === 'inbox' ? 'Inbox' : 'Sent Messages'; ?>
                                </h3>
                            </div>
                            
                            <?php if ($messages->num_rows > 0): ?>
                                <div class="divide-y divide-gray-200">
                                    <?php while ($message = $messages->fetch_assoc()): ?>
                                        <a href="?page=<?php echo $current_page; ?>&id=<?php echo $message['id']; ?>" 
                                           class="block message-item <?php echo (!$message['is_read'] && $current_page === 'inbox') ? 'unread' : ''; ?>">
                                            <div class="p-4 hover:bg-gray-50">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex-1">
                                                        <h4 class="font-semibold text-gray-800 truncate">
                                                            <?php echo htmlspecialchars($message['subject']); ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600 truncate">
                                                            <?php echo $current_page === 'inbox' ? 
                                                                'From: ' . htmlspecialchars($message['sender_name']) . ' (' . ucfirst($message['sender_role']) . ')' :
                                                                'To: ' . htmlspecialchars($message['receiver_name']) . ' (' . ucfirst($message['receiver_role']) . ')'; ?>
                                                        </p>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="text-xs text-gray-500 block">
                                                            <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                        </span>
                                                        <?php if (!$message['is_read'] && $current_page === 'inbox'): ?>
                                                            <span class="inline-block mt-1 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                                                New
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-700 truncate">
                                                    <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                                    <?php echo strlen($message['message']) > 100 ? '...' : ''; ?>
                                                </p>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-envelope-open text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No messages found</p>
                                    <p class="text-gray-400 text-sm mt-2">
                                        <?php echo $current_page === 'inbox' ? 
                                            'Your inbox is empty. Messages you receive will appear here.' :
                                            'You haven\'t sent any messages yet.'; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Message Modal -->
    <div id="composeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">Compose New Message</h3>
            </div>
            
            <form method="POST" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To:</label>
                        <select name="receiver_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Recipient</option>
                            <?php 
                            // Reset pointer and fetch users again
                            $users_result->data_seek(0);
                            while ($user = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo ucfirst($user['role']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject:</label>
                        <input type="text" name="subject" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message:</label>
                        <textarea name="message" rows="6" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Type your message here..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeComposeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="send_message" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openComposeModal() {
            document.getElementById('composeModal').classList.remove('hidden');
            document.getElementById('composeModal').classList.add('flex');
        }

        function closeComposeModal() {
            document.getElementById('composeModal').classList.add('hidden');
            document.getElementById('composeModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('composeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeComposeModal();
            }
        });
    </script>
</body>
</html>