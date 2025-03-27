<?php
require_once 'config.php';

if(!isLoggedIn() || !isDirector()) {
    redirect('login.php', 'Accès non autorisé.', 'error');
}

// Get statistics
$stats = [
    'total_animals' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM animals"))['count'],
    'total_species' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM species"))['count'],
    'total_employees' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='salarie'"))['count'],
    'species_distribution' => [],
    'gender_distribution' => [],
    'monthly_additions' => [],
    'daily_additions' => []
];

// Get species distribution
$sql = "SELECT s.name, COUNT(a.id) as count 
        FROM species s 
        LEFT JOIN animals a ON s.id = a.species_id 
        GROUP BY s.id";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $stats['species_distribution'][] = $row;
}

// Get gender distribution
$sql = "SELECT gender, COUNT(*) as count FROM animals GROUP BY gender";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $stats['gender_distribution'][] = $row;
}

// Get monthly additions
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM animals 
        GROUP BY month 
        ORDER BY month DESC 
        LIMIT 12";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $stats['monthly_additions'][] = $row;
}

// Get daily additions
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as day, COUNT(*) as count 
        FROM animals 
        GROUP BY day 
        ORDER BY day DESC 
        LIMIT 30";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $stats['daily_additions'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Zoo Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-paw"></i> Zoo Management
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="employee_dashboard.php"><i class="fas fa-paw"></i> Gestion des Animaux</a>
                <a href="species_list.php"><i class="fas fa-dna"></i> Liste des Espèces</a>
                <?php if(isDirector()): ?>
                    <a href="director_dashboard.php"><i class="fas fa-users"></i> Gestion des Employés</a>
                    <a href="statistics.php" class="active"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <?php endif; ?>
                <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?> (Directeur)</span>
            </div>
        </nav>
    </header>
    
    <main class="dashboard">
        <h1>Statistiques du Zoo</h1>
        
        <div class="dashboard-stats">
            <!-- Overview statistics -->
        </div>
        
        <div class="dashboard-container">
            <!-- Charts and detailed statistics -->
            <div class="dashboard-card">
                <canvas id="speciesChart"></canvas>
            </div>
            
            <div class="dashboard-card">
                <canvas id="genderChart"></canvas>
            </div>
            
            <div class="dashboard-card full-width">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Graphique de distribution des espèces
            new Chart(document.getElementById('speciesChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($stats['species_distribution'], 'name') ?: []); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($stats['species_distribution'], 'count') ?: []); ?>,
                        backgroundColor: ['#4A7C59', '#F4A261', '#2A9D8F', '#E76F51', '#E9C46A']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' },
                        title: {
                            display: true,
                            text: 'Distribution des espèces'
                        }
                    }
                }
            });

            // Graphique de distribution des genres
            new Chart(document.getElementById('genderChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Mâles', 'Femelles'],
                    datasets: [{
                        data: <?php 
                            $males = 0;
                            $females = 0;
                            foreach($stats['gender_distribution'] as $gender) {
                                if($gender['gender'] == 'male') $males = $gender['count'];
                                if($gender['gender'] == 'female') $females = $gender['count'];
                            }
                            echo json_encode([$males, $females]);
                        ?>,
                        backgroundColor: ['#4A90E2', '#E2498A']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' },
                        title: {
                            display: true,
                            text: 'Distribution par genre'
                        }
                    }
                }
            });

            // Graphique des ajouts journaliers
            new Chart(document.getElementById('monthlyChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($stats['daily_additions'], 'day') ?: []); ?>,
                    datasets: [{
                        label: 'Nouveaux animaux',
                        data: <?php echo json_encode(array_column($stats['daily_additions'], 'count') ?: []); ?>,
                        borderColor: '#4A7C59',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'Évolution journalière des ajouts'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : null;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
