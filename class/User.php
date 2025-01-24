<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = 'users';

    // User Properties
    public $id;
    public $name;
    public $email;
    public $phone;
    public $birthdate;
    public $age;
    public $gender;
    public $password;
    public $interests; // Store interests
    public $favorite_color;
    public $profile_picture;
    public $blood_group;
    public $address;
    public $skills;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Validation Methods
    public function validateName($name) {
        return preg_match("/^[a-zA-Z ]{3,50}$/", $name);
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhone($phone) {
        return preg_match("/^[0-9]{10}$/", $phone);
    }

    public function validateAge($age) {
        return $age >= 18 && $age <= 100;
    }

    public function validatePassword($password) {
        return preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $password);
    }

    public function validateBloodGroup($blood_group) {
        $valid_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return in_array($blood_group, $valid_groups);
    }

    public function validateAddress($address) {
        return strlen(trim($address)) >= 10 && strlen(trim($address)) <= 255;
    }

    public function validateSkills($skills) {
        $valid_skills = [
            'Programming', 'Design', 'Writing', 'Marketing', 
            'Data Analysis', 'Management', 'Sales', 'Customer Service'
        ];
        
        if (!is_array($skills) || empty($skills)) {
            return false;
        }

        foreach ($skills as $skill) {
            if (!in_array($skill, $valid_skills)) {
                return false;
            }
        }

        return true;
    }

    // CRUD Operations for PostgreSQL
    public function create() {
        // Convert interests array to comma-separated string
        $interests_string = is_array($this->interests) ? implode(',', $this->interests) : '';
        $skills_string = is_array($this->skills) ? implode(',', $this->skills) : '';

        $query = "INSERT INTO {$this->table_name} (
            name, email, phone, birthdate, age, gender, password, 
            interests, favorite_color, profile_picture,
            blood_group, address, skills
        ) VALUES (
            $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13
        ) RETURNING id";
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->birthdate,
            $this->age, 
            $this->gender, 
            $hashed_password,
            $interests_string,
            $this->favorite_color,
            $this->profile_picture,
            $this->blood_group,
            $this->address,
            $skills_string
        ];

        try {
            $result = pg_query_params($this->conn, $query, $params);
            
            if ($result) {
                $row = pg_fetch_row($result);
                $this->id = $row[0];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }


    public function read() {
        $query = "SELECT 
            id, name, email, phone, birthdate, age, gender, 
            interests, favorite_color, profile_picture,
            blood_group, address, skills
        FROM {$this->table_name}";
        return pg_query($this->conn, $query);
    }

    public function readById($id) {
        $query = "SELECT 
            id, name, email, phone, birthdate, age, gender, 
            string_to_array(interests, ',') as interests, 
            favorite_color, profile_picture,
            blood_group, address, 
            string_to_array(skills, ',') as skills
        FROM {$this->table_name} 
        WHERE id = $1";
        
        $result = pg_query_params($this->conn, $query, [$id]);
        return pg_fetch_assoc($result);
    }

    // Update method to include new fields
    public function update() {
        $interests_string = is_array($this->interests) ? implode(',', $this->interests) : '';
        $skills_string = is_array($this->skills) ? implode(',', $this->skills) : '';

        $query = "UPDATE {$this->table_name} 
                  SET name=$1, email=$2, phone=$3, 
                      birthdate=$4, age=$5, gender=$6, 
                      interests=$7, favorite_color=$8, 
                      profile_picture=$9, blood_group=$10, 
                      address=$11, skills=$12
                  WHERE id=$13";
        
        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->birthdate,
            $this->age, 
            $this->gender, 
            $interests_string,
            $this->favorite_color,
            $this->profile_picture,
            $this->blood_group,
            $this->address,
            $skills_string,
            $this->id
        ];

        try {
            $result = pg_query_params($this->conn, $query, $params);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        $query = "DELETE FROM {$this->table_name} WHERE id=$1";
        
        try {
            $result = pg_query_params($this->conn, $query, [$id]);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("User deletion error: " . $e->getMessage());
            return false;
        }
    }

    // Additional helper methods
    public function calculateAge($birthdate) {
        $birth = new DateTime($birthdate);
        $today = new DateTime('today');
        return $birth->diff($today)->y;
    }

    public function checkEmailExists($email) {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        $row = pg_fetch_row($result);
        return $row[0] > 0;
    }

    // Authentication method
    public function authenticate($email, $password) {
        $query = "SELECT password FROM {$this->table_name} WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        $user = pg_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        return false;
    }
}
?>