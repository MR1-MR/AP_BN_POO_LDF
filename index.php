<?php
    session_start(); // Démarrage de la session pour conserver l'état du jeu

    // Définition de la taille de la grille
    define('GRID_SIZE', 5);

    // Classe représentant un bateau
    class Ship
    {
        private $position; // Stocke la position du bateau sous forme d'un tableau associatif ['x' => ..., 'y' => ...]

        public function __construct($x, $y)
        {
            $this->position = ['x' => $x, 'y' => $y];
        }

        public function getPosition()
        {
            return $this->position; // Retourne la position du bateau
        }
    }

    // Classe représentant le plateau de jeu
    class Board
    {
        private $grid;  // Stocke l'état de la grille de jeu
        private $ships; // Liste des bateaux placés sur le plateau
        private $hits;  // Nombre de tirs réussis

        public function __construct()
        {
            $this->grid = array_fill(0, GRID_SIZE, array_fill(0, GRID_SIZE, '-')); // Initialise la grille vide
            $this->ships = []; // Initialise la liste des bateaux
            $this->hits = 0; // Compteur de tirs réussis
        }


        public function addShip($x, $y)
        {
            if (count($this->ships) < 3) { // Vérifie qu'il reste des places disponibles pour les bateaux
                $this->ships[] = new Ship($x, $y); // Ajoute un nouveau bateau
            }
        }

        public function getGrid()
        {
            return $this->grid;
        }

        public function getShips()
        {
            return $this->ships;
        }

        public function getHits()
        {
            return $this->hits;
        }

        public function shootAt($x, $y)
        {
            foreach ($this->ships as $ship) { // Parcourt tous les bateaux pour voir si la position correspond
                $position = $ship->getPosition();
                if ($position['x'] === $x && $position['y'] === $y) { // Si un bateau est touché
                    $this->grid[$x][$y] = 'X'; // Marque un tir réussi sur la grille
                    $this->hits++;
                    return true; // Indique que le tir est réussi
                }
            }
            $this->grid[$x][$y] = 'O'; // Marque un tir raté sur la grille
            return false; // Indique que le tir a échoué
        }

        public function isGameOver()
        {
            return $this->hits === count($this->ships); // Vérifie si tous les bateaux ont été coulés
        }
    }

    // Classe principale qui gère le jeu
    class Game
    {
        private $board; // Instance du plateau de jeu
        private $message; // Message affiché à l'utilisateur

        public function __construct()
        {
            if (!isset($_SESSION['board']) || $_SESSION['board'] === null) {
                $this->board = new Board(); // Créer une nouvelle instance
                $this->initializeGame(); // Ajouter les bateaux
                $_SESSION['board'] = $this->board; // Stocker l'objet en session
            } else {
                $this->board = $_SESSION['board']; // Récupération du plateau existant
            }

            $this->message = "Choisissez une case sur la grille pour trouver les bateaux.";
        }

        private function initializeGame()
        {
            while (count($this->board->getShips()) < 3) {
                $x = rand(0, GRID_SIZE - 1);
                $y = rand(0, GRID_SIZE - 1);

                // Vérifie si un bateau existe déjà à cette position
                $alreadyPlaced = false;
                foreach ($this->board->getShips() as $ship) {
                    $position = $ship->getPosition();
                    if ($position['x'] === $x && $position['y'] === $y) {
                        $alreadyPlaced = true;
                        break;
                    }
                }
                if (!$alreadyPlaced) {
                    $this->board->addShip($x, $y);
                }
            }
        }

        public function processMove($x, $y)
        {
            if ($x < 0 || $x >= GRID_SIZE || $y < 0 || $y >= GRID_SIZE) {
                $this->message = "Coordonnées invalides. Essayez à nouveau.";
            } else {
                $hit = $this->board->shootAt($x, $y); // Tente de tirer sur la case sélectionnée
                $this->message = $hit ? "Touché!" : "À l'eau.";

                if ($this->board->isGameOver()) {
                    $this->message = "Félicitations, vous avez coulé tous les bateaux!";
                    session_destroy(); // Réinitialise la session une fois le jeu terminé
                }
            }
        }

        public function getMessage()
        {
            return $this->message;
        }

        public function getGrid()
        {
            return $this->board->getGrid();
        }
    }

    $game = new Game(); // Création ou récupération de l'objet jeu

    // Gestion du formulaire pour tirer sur une case
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $x = ord(strtoupper($_POST['x'])) - ord('A');
        $y = intval($_POST['y']) - 1;
        $game->processMove($x, $y);
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Jeu - Bataille Navale</title>
    <style>
        table { border-collapse: collapse; margin: 20px auto; }
        td { width: 60px; height: 60px; text-align: center; border: 1px solid black; font-weight: bold; }
        form { text-align: center; }
        h1, p { text-align: center; }
        select { padding: 5px; margin: 0 5px; }
        img { width: 50px; height: 50px; }
    </style>
</head>
<body>
    <h1>Bataille Navale</h1>
    <p>Il y a 3 bateaux à couler. Bonne chance !</p>
    <p><?= htmlspecialchars($game->getMessage()) ?></p>

    <table>
        <tr>
            <th></th>
            <?php for ($i = 1; $i <= GRID_SIZE; $i++) { ?>
                <th><?= $i ?></th>
            <?php } ?>
        </tr>
        <?php for ($i = 0; $i < GRID_SIZE; $i++) { ?>
            <tr>
                <th><?= chr($i + ord('A')) ?></th>
                <?php for ($j = 0; $j < GRID_SIZE; $j++) { ?>
                    <td>
                        <?php if ($game->getGrid()[$i][$j] === 'X') { ?>
                            <img src="images/boat.png" alt="Bateau">
                        <?php } elseif ($game->getGrid()[$i][$j] === 'O') { ?>
                            <img src="images/water.png" alt="À l'eau">
                        <?php } else { ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="x" value="<?= chr($i + ord('A')) ?>">
                                <input type="hidden" name="y" value="<?= $j + 1 ?>">
                                <button type="submit" style="width: 100%; height: 100%; background: none; border: none; cursor: pointer;">
                                    <?= htmlspecialchars($game->getGrid()[$i][$j]) ?>
                                </button>
                            </form>
                        <?php } ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
