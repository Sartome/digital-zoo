<?php
// animal_management.php

// Database connection
$host = 'localhost';
$dbname = 'zoo_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to fetch all animals
function getAllAnimals($pdo) {
    $stmt = $pdo->query("SELECT * FROM animals");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new animal
function addAnimal($pdo, $name, $species, $age) {
    $stmt = $pdo->prepare("INSERT INTO animals (name, species, age) VALUES (:name, :species, :age)");
    $stmt->execute(['name' => $name, 'species' => $species, 'age' => $age]);
}

// Function to delete an animal
function deleteAnimal($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM animals WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        addAnimal($pdo, $_POST['name'], $_POST['species'], $_POST['age']);
    } elseif (isset($_POST['delete'])) {
        deleteAnimal($pdo, $_POST['id']);
    }
}

// Fetch all animals for display
$animals = getAllAnimals($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Management</title>
</head>
<body>
    <h1>Animal Management</h1>

    <h2>Add New Animal</h2>
    <form method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="species">Species:</label>
        <input type="text" id="species" name="species" required>
        <label for="age">Age:</label>
        <input type="number" id="age" name="age" required>
        <button type="submit" name="add">Add Animal</button>
    </form>

    <h2>Animal List</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Species</th>
                <th>Age</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($animals as $animal): ?>
                <tr>
                    <td><?= htmlspecialchars($animal['id']) ?></td>
                    <td><?= htmlspecialchars($animal['name']) ?></td>
                    <td><?= htmlspecialchars($animal['species']) ?></td>
                    <td><?= htmlspecialchars($animal['age']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $animal['id'] ?>">
                            <button type="submit" name="delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>