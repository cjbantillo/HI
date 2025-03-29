<?php
// Include database connection from db_connection.php
include('db_connection.php');

// Add a new task if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task'])) {
    $task = $_POST['task']; // No need to sanitize here, JS will handle it
    $taskId = isset($_POST['taskId']) ? $_POST['taskId'] : null;

    if (!empty($task)) {
        if ($taskId) {
            // Update the task if taskId is provided
            $stmt = $conn->prepare("UPDATE tasks SET task = ? WHERE id = ?");
            $stmt->bind_param('si', $task, $taskId);  // 's' for string, 'i' for integer
        } else {
            // Insert new task if no taskId
            $stmt = $conn->prepare("INSERT INTO tasks (task, completed) VALUES (?, 0)");
            $stmt->bind_param('s', $task);  // 's' for string type
        }
        $stmt->execute();
    }
}

// Delete a task if requested
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete task from database
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param('i', $id); // 'i' for integer type
    $stmt->execute();
}

// Toggle task completion
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $conn->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

// Fetch tasks from the database
$stmt = $conn->prepare("SELECT * FROM tasks");
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);  // Fetch all tasks as an associative array
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <style>
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #3A9C9F;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        /* Header */
        h2 {
            color: black;
            text-align: center;
            font-size: 30px;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Main container for the to-do list */
        .todo-container {
            width: 350px;
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        /* Task input */
        input[type="text"] {
            width: 80%;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #ccc;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s;
        }

        input[type="text"]:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(72, 181, 97, 0.5);
        }

        /* Add task button */
        button {
            width: 50%;
            padding: 12px;
            font-size: 18px;
            color: white;
            background-color: #4CAF50;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Task list styling */
        .todo-list {
            list-style-type: none;
            padding: 0;
            margin-top: 20px;
        }

        .todo-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f3f3f3;
            margin: 10px 0;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }

        .todo-list li:hover {
            background-color: #e9f7e7;
        }

        /* Checkbox for completed tasks */
        .todo-list input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        /* Strikethrough styling for completed tasks */
        .completed-task {
            text-decoration: line-through;
            color: #7f8c8d;
        }

        /* Task delete button */
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        /* Edit button styling */
        .edit-btn {
            background-color: #f39c12;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
        }

        .edit-btn:hover {
            background-color: #e67e22;
        }

        /* Save button styling */
        .save-btn {
            background-color: #2ecc71;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
        }

        .save-btn:hover {
            background-color: #27ae60;
        }

        /* Cancel button styling */
        .cancel-btn {
            background-color: #e67e22;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .cancel-btn:hover {
            background-color: #f39c12;
        }

        /* Progress bar */
        .progress {
            margin-top: 20px;
            font-size: 16px;
        }

        .progress span {
            color: #4CAF50;
            font-weight: bold;
        }

        /* Responsive design */
        @media (max-width: 400px) {
            .todo-container {
                width: 90%;
            }
            button {
                width: 60%;
            }
        }
    </style>

    <script>
        // Confirm before deleting task
        function confirmDelete(url) {
            if (confirm('Are you sure you want to delete this task?')) {
                window.location = url;
            }
        }

        // Sanitize input before submitting
        function sanitizeInput(input) {
            // Replace any harmful HTML tags or special characters
            var element = document.createElement('div');
            element.textContent = input;  // Automatically escapes any harmful characters
            return element.innerHTML;  // Return the sanitized text
        }

        // Validate input using regex to allow only safe characters
        function validateInput(task) {
            var regex = /^[a-zA-Z0-9\s\.\,\!\?\-]+$/;  // Only letters, numbers, spaces, and common punctuation
            return regex.test(task);
        }

        // Handling form submit
        function handleFormSubmit(event) {
            var taskInput = document.querySelector('input[name="task"]').value;

            // Sanitize input
            taskInput = sanitizeInput(taskInput);

            // Validate input
            if (!validateInput(taskInput) || taskInput.length > 50) {
                alert("Invalid task. Only letters, numbers, spaces, and basic punctuation are allowed. Please limit it to 50 characters.");
                event.preventDefault();  // Prevent form submission if invalid
            } else {
                // Set the sanitized value back to the input before submission
                document.querySelector('input[name="task"]').value = taskInput;
            }
        }
    </script>
</head>
<body>

<div class="todo-container">
    <h2>To-Do List</h2>
    <!-- Task input form -->
    <form action="index.php" method="post" onsubmit="handleFormSubmit(event)">
        <input type="text" name="task" placeholder="What needs to be done?" required />
        <button type="submit">+</button>
    </form>

    <!-- Display tasks -->
    <ul class="todo-list">
        <?php foreach ($tasks as $task): ?>
            <li>
                <input type="checkbox" <?php echo $task['completed'] ? 'checked' : ''; ?> onclick="window.location='?toggle=<?php echo $task['taskId']; ?>'">
                
                <?php if (isset($_GET['edit']) && $_GET['edit'] == $task['id']): ?>
                    <!-- Editing the task -->
                    <form action="index.php" method="post">
                        <input type="text" name="task" value="<?php echo htmlspecialchars($task['task']); ?>" required />
                        <input type="hidden" name="taskId" value="<?php echo $task['taskId']; ?>" />
                        <button type="submit" class="save-btn">Save</button>
                        <a href="index.php" class="cancel-btn">Cancel</a>
                    </form>
                <?php else: ?>
                    <!-- Display task -->
                    <span class="<?php echo $task['completed'] ? 'completed-task' : ''; ?>"><?php echo htmlspecialchars($task['task']); ?></span>
                    <!-- Edit button -->
                    <a href="?edit=<?php echo $task['taskId']; ?>" class="edit-btn">✎</a>
                <?php endif; ?>
                
                <!-- Delete button -->
                <a href="javascript:void(0);" onclick="confirmDelete('?delete=<?php echo $task['taskId']; ?>')" class="delete-btn">×</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Progress Bar -->
    <div class="progress">
        <?php
        $totalTasks = count($tasks);
        $completedTasks = array_filter($tasks, fn($task) => $task['completed'] == 1);
        $completedCount = count($completedTasks);
        echo "<span>$completedCount</span> of <span>$totalTasks</span> tasks done";
        ?>
    </div>

</div>

</body>
</html>
