<?php
session_start();
require "config.php";


if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['generate_key'])) {
        $key = empty($_POST['custom_key']) ? bin2hex(random_bytes(6)) : $_POST['custom_key'];
        $days = isset($_POST['expiry_days']) && is_numeric($_POST['expiry_days']) ? $_POST['expiry_days'] : 3;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$days days"));

        // made by enzosrs
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE `key` = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $errorMessage = "Key already exists. Please generate a new one.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (`key`, expires_at) VALUES (?, ?)");
                $stmt->execute([$key, $expiresAt]);
                $successMessage = "Key generated successfully!";
            } catch (Exception $e) {
                $errorMessage = "Failed to generate key.";
            }
        }
    }

    if (isset($_POST['delete_key'])) {
        $deleteKey = $_POST['delete_key'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE `key` = ?");
            $stmt->execute([$deleteKey]);
            $successMessage = "Key deleted successfully!";
        } catch (Exception $e) {
            $errorMessage = "Failed to delete key.";
        }
    }

    if (isset($_POST['edit_timer'])) {
        $newExpiry = $_POST['new_expiry'];
        $keyToUpdate = $_POST['key_to_update'];

        try {
            $stmt = $pdo->prepare("UPDATE users SET expires_at = ? WHERE `key` = ?");
            $stmt->execute([$newExpiry, $keyToUpdate]);
            $successMessage = "Key expiration updated successfully!";
        } catch (Exception $e) {
            $errorMessage = "Failed to update expiration.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users");
$keys = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>TKS Admin Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-image: url('dragon-image.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: #fff;
        }
        .container {
            font: italic small-caps bold 12px/30px Georgia, serif;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: bold;
            max-width: 800px;
            margin: auto;
            padding: 10px;
        }
        
         .container i {
            height: 40px;
            width: 40px;
            margin-bottom: 20px;
            margin: auto;
            padding: 10px;
        }
        
        .card {
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        .input-field {
            font-family: "Lucida Console", "Courier New", monospace;
            font-weight: bold;
            width: 100%;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #4a5568;
            background-color: #2d3748;
            color: #fff;
            font-size: 18px;
        }
        .input-field:focus {
            font-family: "Lucida Console", "Courier New", monospace;
            border-color: #ecc94b;
            outline: none;
        }
        .btn {
            font: italic small-caps bold 12px/30px Georgia, serif;
            margin-bottom: 20px;
            font-weight: bold;
            padding: 10px;
            border-radius: 15px;
            font-size: 14px;
            cursor: pointer;
            text-align: center;
            width: 100%;
        }
        .btn-green {
            background-color: #38a169;
            color: white;
        }
        .btn-green:hover {
            background-color: #2f855a;
        }
        .btn-blue {
            background-color: #3182ce;
            color: white;
        }
        .btn-blue:hover {
            background-color: #2b6cb0;
        }
        .btn-red {
            background-color: #e53e3e;
            color: white;
        }
        .btn-red:hover {
            background-color: #c53030;
        }
        .icon {
            margin-right: 10px;
        }
        @media (max-width: 768px) {
            .input-field {
                font-size: 16px;
            }
            .btn {
                font-size: 16px;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
<div class="container">
    <div class="card text-center">
        <h1 class="text-3xl font-bold mb-2">Admin Panel</h1>
        <p class="text-red-500 text-lg mb-4">Developed By Tks</p>

        <?php if ($successMessage): ?>
            <div class="bg-green-500 text-white p-3 rounded mb-4"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

     
        <form method="POST" class="mb-6">
            <div class="mb-4">
                <input type="text" name="custom_key" class="input-field" placeholder="Enter key Username">
            </div>
            <div class="mb-4">
                <input type="number" name="expiry_days" class="input-field" placeholder="Duration (days)" min="1" max="365">
            </div>
             <br>
            <button type="submit" name="generate_key" class="btn btn-green">
                <i class="fas fa-plus icon"></i> Generate Key
            </button>
        </form>

        <button onclick="window.location.href='admin.php'" class="btn btn-blue">
            <i class="fas fa-arrow-left icon"></i> Go Back to Home
        </button>
    </div>

    
    <div class="mt-10">
        <h3 class="text-2xl font-semibold text-center mb-4">All Keys</h3>
        <div class="space-y-4">
            <?php foreach ($keys as $key): ?>
                <div class="card">
                    <p class="text-lg"><i class="fas fa-user-circle text-yellow-400 icon"></i>ID: <?= htmlspecialchars($key['id']) ?></p>
                    <p class="text-lg"><i class="fas fa-key text-green-500 icon"></i>Key: <?= htmlspecialchars($key['key']) ?></p>
                    <p class="text-lg"><i class="fas fa-mobile-alt text-purple-400 icon"></i>Device ID: <?= htmlspecialchars($key['device_id']) ?></p>
                    <p class="text-lg"><i class="fas fa-hourglass text-blue-400 icon"></i>Expire: <?= htmlspecialchars($key['expires_at']) ?></p>
                    
                 
                    <div class="mt-4">
                        <form method="POST">
                            <input type="hidden" name="key_to_update" value="<?= htmlspecialchars($key['key']) ?>">
                            <div class="mb-2">
                                <input type="datetime-local" name="new_expiry" class="input-field">
                            </div>
                            <br>
                            <button type="submit" name="edit_timer" class="btn btn-blue">
                                <i class="fas fa-edit icon"></i> Update
                            </button>
                        </form>

                        <form method="POST" class="mt-2">
                            <input type="hidden" name="delete_key" value="<?= htmlspecialchars($key['key']) ?>">
                            <button type="submit" class="btn btn-red">
                                <i class="fas fa-trash icon"></i> Delete
                            </button>
                        </form>

                        <button onclick="copyUserDetails('<?= htmlspecialchars($key['id']) ?>', '<?= htmlspecialchars($key['key']) ?>', '<?= htmlspecialchars($key['status']) ?>', '<?= htmlspecialchars($key['expires_at']) ?>', '<?= htmlspecialchars($key['device_id']) ?>')" class="btn btn-blue mt-2">
                            <i class="fas fa-copy icon"></i> Copy Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function copyUserDetails(id, key, status, expires, device_id) {
        let text = 
        `☠️ 𝑻𝑲𝑺 𝑼𝑳𝑻𝑹𝑨 𝐌𝐎𝐃 2.1 ☠️\n\n\n🔑 𝐊𝐞𝐲:- ${key}\n\n⏳ 𝐄𝐱𝐩𝐢𝐫𝐞:- ${expires}\n\n🧑‍💻 Mᴀᴅᴇ Bʏ:- @TKSOFFICIAL0\n\n👤 Oᴡɴᴇʀ:- @TKS091`;
        navigator.clipboard.writeText(text).then(() => {
            alert("User details copied!");
        });
    }
</script>
</body>
</html>