<?php
// Include Supabase connection
include('db_connection.php');

// Start session for error persistence (optional)
session_start();

// Handle form submissions (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['task'])) {
        $task = htmlspecialchars(trim($_POST['task']));
        $taskId = isset($_POST['taskId']) ? filter_var($_POST['taskId'], FILTER_SANITIZE_NUMBER_INT) : null;

        if (!empty($task)) {
            try {
                if ($taskId) {
                    // Update existing task
                    $response = supabase_request('PATCH', "/rest/v1/tasks?taskId=eq.$taskId", [
                        'task' => $task,
                        'updated_at' => date('c') // ISO 8601 timestamp
                    ]);
                } else {
                    // Create new task
                    $response = supabase_request('POST', '/rest/v1/tasks', [
                        'task' => $task,
                        'completed' => '0', // Use string '0' since column is varchar
                        'updated_at' => date('c')
                    ]);
                }
                
                if (!$response) {
                    $_SESSION['error'] = "Failed to save task";
                } else {
                    unset($_SESSION['error']);
                    header("Location: index.php");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle task deletion
if (isset($_GET['delete'])) {
    try {
        $taskId = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
        if (!$taskId || !is_numeric($taskId)) {
            throw new Exception("Invalid task ID");
        }
        $response = supabase_request('DELETE', "/rest/v1/tasks?taskId=eq.$taskId");
        if (!$response) {
            $_SESSION['error'] = "Failed to delete task";
        } else {
            unset($_SESSION['error']);
            header("Location: index.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting task: " . $e->getMessage();
    }
}

// Handle task toggle
if (isset($_GET['toggle'])) {
    try {
        $taskId = filter_var($_GET['toggle'], FILTER_SANITIZE_NUMBER_INT);
        
        if (!$taskId || !is_numeric($taskId)) {
            throw new Exception("Invalid task ID");
        }

        $singleTask = supabase_request('GET', "/rest/v1/tasks?taskId=eq.$taskId");
        
        if (!$singleTask || !is_array($singleTask) || empty($singleTask)) {
            throw new Exception("Task not found");
        }

        $singleTask = $singleTask[0];
        
        if (!isset($singleTask['completed'])) {
            throw new Exception("Task data is malformed");
        }

        $newStatus = $singleTask['completed'] === '1' ? '0' : '1';
        
        $response = supabase_request('PATCH', "/rest/v1/tasks?taskId=eq.$taskId", [
            'completed' => $newStatus
        ]);
        
        if (!$response) {
            $_SESSION['error'] = "Failed to toggle task";
        } else {
            unset($_SESSION['error']);
            header("Location: index.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error toggling task: " . $e->getMessage();
    }
}

// Fetch tasks with sorting
try {
    $tasks = supabase_request('GET', '/rest/v1/tasks?order=taskId.desc');
    if ($tasks === null || $tasks === false) {
        $_SESSION['error'] = "Failed to fetch tasks";
        $tasks = [];
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching tasks: " . $e->getMessage();
    $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>To-Do List</title>
</head>
<body>
<div class="todo-container">
    <h2>To-Do List</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <!-- Task input form -->
    <form action="index.php" method="post" class="edit-form">
        <input type="text" name="task" placeholder="What needs to be done?" required>
        <button type="submit">+</button>
    </form>

    <!-- Display tasks -->
    <ul class="todo-list">
        <?php 
        $tasks = isset($tasks) && is_array($tasks) ? $tasks : [];
        
        if (!empty($tasks)): 
            foreach ($tasks as $task): 
                $isCompleted = isset($task['completed']) && $task['completed'] === '1';
        ?>
            <li>
                <?php if (isset($_GET['edit']) && $_GET['edit'] == $task['taskId']): ?>
                    <form action="index.php" method="post" class="edit-form">
                        <input type="hidden" name="taskId" value="<?php echo $task['taskId']; ?>">
                        <input type="text" name="task" value="<?php echo htmlspecialchars($task['task']); ?>" required>
                        <button type="submit">✓</button>
                    </form>
                <?php else: ?>
                    <input type="checkbox" 
                           <?php echo $isCompleted ? 'checked' : ''; ?> 
                           onclick="window.location='?toggle=<?php echo $task['taskId']; ?>'">
                    <span class="<?php echo $isCompleted ? 'completed-task' : ''; ?>">
                        <?php echo htmlspecialchars($task['task']); ?>
                    </span>
                    <div>
                        <a href="?edit=<?php echo $task['taskId']; ?>" class="edit-btn">✎</a>
                        <a href="?delete=<?php echo $task['taskId']; ?>" 
                           class="delete-btn" 
                           onclick="return confirm('Are you sure you want to delete this task?')">×</a>
                    </div>
                <?php endif; ?>
            </li>
        <?php 
            endforeach; 
        else: 
        ?>
            <li>No tasks yet!</li>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>