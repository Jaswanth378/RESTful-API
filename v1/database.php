<?php

/* The Database class is taken reference from the example provided in lecture 30, slide 12 */
class Database {
    private $host = "studdb.csc.liv.ac.uk";
    private $user = "sgjkattu";
    private $passwd = "Greenrose@99";
    private $database = "sgjkattu";
    
    public $connection;

    /* This constructor and PDO options are referenced from the one in lecture 30, slide 12 */
    public function __construct() {
        $opt = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );

        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->database;charset=utf8mb4", $this->user, $this->passwd, $opt);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 503);
        }
    }

    /* The createTables() method creates the 'teams' and 'players' tables if they don't exist */
    public function createTables() {
        $sql = "CREATE TABLE IF NOT EXISTS teams (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(30) NOT NULL,
            sport VARCHAR(30) NOT NULL
        )";
        $this->conn->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS players (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            team_id INT(6) UNSIGNED NOT NULL,
            surname VARCHAR(30) NOT NULL,
            given_names VARCHAR(30) NOT NULL,
            nationality VARCHAR(30) NOT NULL,
            dob DATE NOT NULL
        )";
        $this->conn->exec($sql);
    }

    /* The insertDummyData() method below inserts dummy data into the 'teams' and 'players' tables if they are empty */
    public function insertDummyData() {
        $teamCount = $this->conn->query("SELECT COUNT(*) FROM teams")->fetchColumn();
        if ($teamCount == 0) {
            $sql = "INSERT INTO teams (name, sport) VALUES
                ('CSK', 'Cricket'),
                ('RCB', 'Basketball'),
                ('SRH', 'Soccer')";
            $this->conn->exec($sql);
        }

        $playerCount = $this->conn->query("SELECT COUNT(*) FROM players")->fetchColumn();
        if ($playerCount == 0) {
            $sql = "INSERT INTO players (team_id, surname, given_names, nationality, dob) VALUES
                (1, 'Ruturaj', 'Gaikwad', 'Indian', '1987-02-01'),
                (1, 'Jadeja', 'Ravindra', 'British', '1972-04-15'),
                (1, 'Bravo', 'Dwane', 'West Indies', '1988-06-20'),
                (2, 'James', 'LeBron', 'Australian', '1974-09-10'),
                (2, 'Michael', 'Jordan', 'New Zealand', '1986-01-30'),
                (2, 'Larry', 'Bird', 'South African', '1984-03-05'),
                (3, 'Ronaldo', 'Cristiano', 'Portugese', '1995-07-12'),
                (3, 'Messi', 'Lionel', 'Argentine', '1987-06-24'),
                (3, 'Sunil', 'Chhetri', 'Indian', '1984-08-03')";
            $this->conn->exec($sql);
        }
    }
}

?>