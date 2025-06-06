<?php
// Database connection
$host = 'localhost';
$dbname = 'ai_todo';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $taskName = $_POST['task_name'];
        $taskDesc = $_POST['task_description'];
        $dueDate = $_POST['due_date'];
        $priority = $_POST['priority'];
        
        $stmt = $pdo->prepare("INSERT INTO tasks (task_name, task_description, due_date, priority) VALUES (?, ?, ?, ?)");
        $stmt->execute([$taskName, $taskDesc, $dueDate, $priority]);
    } elseif (isset($_POST['update_status'])) {
        $taskId = $_POST['task_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $taskId]);
    } elseif (isset($_POST['delete_task'])) {
        $taskId = $_POST['task_id'];
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
    }
}

// Get all tasks
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY 
    FIELD(priority, 'high', 'medium', 'low'), 
    due_date ASC, 
    created_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuralTask AI - Futuristic To-Do List</title>
    <style>
        :root {
            --primary: #00f0ff;
            --secondary: #7b2dff;
            --dark: #0a0a1a;
            --light: #e0e0ff;
            --success: #00ff9d;
            --warning: #ffcc00;
            --danger: #ff3860;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark);
            color: var(--light);
            margin: 0;
            padding: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(123, 45, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(0, 240, 255, 0.1) 0%, transparent 20%);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--secondary);
            padding-bottom: 1rem;
        }
        
        h1 {
            font-size: 2.5rem;
            margin: 0;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 10px rgba(0, 240, 255, 0.3);
        }
        
        .tagline {
            font-size: 1.1rem;
            color: var(--primary);
            margin-top: 0.5rem;
        }
        
        .task-form {
            background: rgba(10, 10, 30, 0.8);
            border: 1px solid var(--secondary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 0 20px rgba(123, 45, 255, 0.2);
        }
        
        .form-title {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.3rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(20, 20, 40, 0.8);
            border: 1px solid var(--secondary);
            border-radius: 4px;
            color: var(--light);
            font-size: 1rem;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 240, 255, 0.3);
        }
        
        button {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 240, 255, 0.4);
        }
        
        .task-list {
            margin-top: 2rem;
        }
        
        .task-card {
            background: rgba(10, 10, 30, 0.8);
            border: 1px solid var(--secondary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(123, 45, 255, 0.1);
        }
        
        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 25px rgba(123, 45, 255, 0.3);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .task-title {
            font-size: 1.2rem;
            margin: 0;
            color: var(--primary);
        }
        
        .task-priority {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .priority-high {
            background-color: rgba(255, 56, 96, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .priority-medium {
            background-color: rgba(255, 204, 0, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .priority-low {
            background-color: rgba(0, 240, 255, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .task-description {
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: rgba(224, 224, 255, 0.7);
        }
        
        .task-due {
            display: flex;
            align-items: center;
        }
        
        .task-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: rgba(255, 204, 0, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .status-in_progress {
            background-color: rgba(0, 240, 255, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .status-completed {
            background-color: rgba(0, 255, 157, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .task-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .update-btn {
            background: rgba(0, 240, 255, 0.1);
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .update-btn:hover {
            background: rgba(0, 240, 255, 0.3);
        }
        
        .delete-btn {
            background: rgba(255, 56, 96, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .delete-btn:hover {
            background: rgba(255, 56, 96, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(224, 224, 255, 0.5);
            border: 2px dashed var(--secondary);
            border-radius: 8px;
        }
        
        .ai-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>NeuralTask AI</h1>
            <p class="tagline">Your futuristic task management system powered by artificial intelligence</p>
        </header>
        
        <div class="task-form">
            <h2 class="form-title">Create New Task</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="task_name">Task Name</label>
                        <input type="text" id="task_name" name="task_name" required placeholder="Enter task name...">
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" required>
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="task_description">Task Description</label>
                    <textarea id="task_description" name="task_description" rows="3" placeholder="Describe your task..."></textarea>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="datetime-local" id="due_date" name="due_date">
                </div>
                <button type="submit" name="add_task">Add Task <span class="ai-pulse">⚡</span></button>
            </form>
        </div>
        
        <div class="task-list">
            <h2>Your Tasks</h2>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <p>No tasks found. The neural network is idle.</p>
                    <p>Add your first task to activate the system.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <h3 class="task-title"><?= htmlspecialchars($task['task_name']) ?></h3>
                            <span class="task-priority priority-<?= $task['priority'] ?>">
                                <?= ucfirst($task['priority']) ?> priority
                            </span>
                        </div>
                        
                        <?php if (!empty($task['task_description'])): ?>
                            <div class="task-description">
                                <?= nl2br(htmlspecialchars($task['task_description'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="task-meta">
                            <div class="task-due">
                                <?php if ($task['due_date']): ?>
                                    <span>⏱️ Due: <?= date('M j, Y g:i A', strtotime($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="task-status status-<?= str_replace(' ', '_', $task['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="task-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="action-btn update-btn">
                                    <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="delete_task" class="action-btn delete-btn">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>